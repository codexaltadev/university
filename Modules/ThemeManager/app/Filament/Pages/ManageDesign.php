<?php

namespace Modules\ThemeManager\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Platform\Filament\Components\MediaPicker;
use Platform\Settings\Settings;
use Platform\Theme\Contrast;
use Platform\Theme\DesignTokens;
use Platform\Theme\Theme;
use Platform\Theme\Themes;

/**
 * Appearance › Design (C-06, UI/UX Part A §7.11): colours with a WCAG contrast
 * warning, fonts (Bangla separately), size and corners, website branding, the
 * theme's own options; "Reset to theme default"; preview of the saved website.
 * Values belong to the active theme (core_settings "theme.{slug}"); branding to the site ("branding").
 */
class ManageDesign extends Page
{
    public const BRANDING = ['logo_light', 'logo_dark', 'logo_footer', 'favicon'];

    /** Colours checked against the page background (text-like use). */
    public const CONTRAST_CHECKED = ['color-text', 'color-text-muted', 'color-primary', 'color-secondary', 'color-danger'];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?int $navigationSort = 51;

    protected static ?string $slug = 'design';

    public ?array $data = [];

    public int $previewVersion = 0;

    public static function getNavigationGroup(): ?string
    {
        return 'Appearance';
    }

    public static function getNavigationLabel(): string
    {
        return __('thememanager::theme.design.title');
    }

    public function getTitle(): string
    {
        return __('thememanager::theme.design.title');
    }

    public function getSubheading(): ?string
    {
        return __('thememanager::theme.design.subheading', ['theme' => $this->theme()->name]);
    }

    public static function canAccess(): bool
    {
        return Themes::allows(Auth::user(), 'Customize:Theme');
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->load();
    }

    public function load(): void
    {
        $theme = $this->theme();

        $this->form->fill([
            'tokens' => app(DesignTokens::class)->values($theme),
            'branding' => array_intersect_key(settings()->group('branding'), array_flip(self::BRANDING)),
            'options' => collect($theme->manifest()->options())
                ->mapWithKeys(fn (array $option): array => [$option['key'] => app(Themes::class)->option($option['key'])])
                ->all(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make(__('thememanager::theme.design.colors'))->schema([
                Group::make()->statePath('tokens')->columns(3)->schema(
                    collect(DesignTokens::COLORS)->map(fn (string $token) => ColorPicker::make($token)
                        ->label(__("thememanager::theme.tokens.{$token}"))
                        ->hex()->regex('/^#[0-9a-fA-F]{6}$/')->required()->live(onBlur: true)
                        ->hint(fn (Get $get, ?string $state): ?string => $this->contrastHint($token, $state, $get('color-bg')))
                        ->hintColor('warning')->hintIcon(fn (Get $get, ?string $state): ?Heroicon => $this->contrastHint($token, $state, $get('color-bg')) ? Heroicon::OutlinedExclamationTriangle : null)
                    )->all()
                ),
            ])->collapsible(),

            Section::make(__('thememanager::theme.design.typography'))->schema([
                Group::make()->statePath('tokens')->columns(3)->schema([
                    Select::make('font-heading')->label(__('thememanager::theme.tokens.font-heading'))->options($this->fonts())->searchable()->required()->selectablePlaceholder(false),
                    Select::make('font-body')->label(__('thememanager::theme.tokens.font-body'))->options($this->fonts())->searchable()->required()->selectablePlaceholder(false),
                    Select::make('font-bn')->label(__('thememanager::theme.tokens.font-bn'))->options($this->fonts())->searchable()->required()->selectablePlaceholder(false)
                        ->helperText(__('thememanager::theme.help.font_bn')),
                    Select::make('font-size-base')->label(__('thememanager::theme.tokens.font-size-base'))
                        ->options(array_combine($sizes = ['14px', '15px', '16px', '17px', '18px'], $sizes))->required()->selectablePlaceholder(false),
                    Select::make('radius-md')->label(__('thememanager::theme.tokens.radius-md'))
                        ->options(array_combine($radii = ['0px', '4px', '6px', '8px', '12px', '16px'], $radii))->required()->selectablePlaceholder(false),
                    Select::make('container-max')->label(__('thememanager::theme.tokens.container-max'))
                        ->options(array_combine($widths = ['1140px', '1280px', '1440px'], $widths))->required()->selectablePlaceholder(false),
                ]),
            ])->collapsible(),

            Section::make(__('thememanager::theme.design.branding'))->description(__('thememanager::theme.help.branding'))->schema([
                Group::make()->statePath('branding')->columns(2)->schema(
                    collect(self::BRANDING)->map(fn (string $key) => MediaPicker::make($key)
                        ->label(__("thememanager::theme.branding.{$key}"))->image()->storeIds())->all()
                ),
            ])->collapsible(),

            Section::make(__('thememanager::theme.design.options'))
                ->visible(fn (): bool => $this->theme()->manifest()->options() !== [])
                ->schema([Group::make()->statePath('options')->columns(2)->schema($this->optionFields())])
                ->collapsible(),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label(__('thememanager::theme.actions.save'))->submit('save')->keyBindings(['mod+s']),
                        Action::make('reset')->label(__('thememanager::theme.actions.reset'))->color('gray')
                            ->requiresConfirmation()->modalDescription(__('thememanager::theme.help.reset'))
                            ->action(fn () => $this->resetDesign()),
                        Action::make('copyToAdmin')->label(__('thememanager::theme.actions.copy_to_admin'))->color('gray')
                            ->requiresConfirmation()->modalDescription(__('thememanager::theme.help.copy_to_admin'))
                            ->action(fn () => $this->copyToAdmin()),
                    ])->key('form-actions'),
                ]),

            Section::make(__('thememanager::theme.design.preview'))->schema([
                View::make('thememanager::preview')->viewData(fn (): array => ['version' => $this->previewVersion]),
            ])->collapsible(),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $state = $this->form->getState();
        $theme = $this->theme();

        app(DesignTokens::class)->save($theme, (array) ($state['tokens'] ?? []));
        app(Themes::class)->saveOptions($theme, (array) ($state['options'] ?? []));
        app(Settings::class)->save('branding', array_intersect_key((array) ($state['branding'] ?? []), array_flip(self::BRANDING)), media: self::BRANDING);

        $this->previewVersion++;
        Notification::make()->success()->title(__('thememanager::theme.notifications.saved'))->send();
    }

    /** FR-THEME-12: the theme's own colours, fonts and options again (branding stays). */
    public function resetDesign(): void
    {
        abort_unless(static::canAccess(), 403);

        $theme = $this->theme();
        app(DesignTokens::class)->reset($theme);
        app(Themes::class)->saveOptions($theme, []);

        $this->load();
        $this->previewVersion++;
        Notification::make()->success()->title(__('thememanager::theme.notifications.reset'))->send();
    }

    /** "Copy website branding" to the admin panel (FR-SET-13): logos, favicon, primary colour. */
    public function copyToAdmin(): void
    {
        abort_unless(static::canAccess(), 403);

        $branding = settings()->group('branding');
        $values = array_filter([
            'logo_light' => $branding['logo_light'] ?? null,
            'logo_dark' => $branding['logo_dark'] ?? null,
            'favicon' => $branding['favicon'] ?? null,
            'primary_color' => app(DesignTokens::class)->values($this->theme())['color-primary'],
        ], fn ($value): bool => filled($value));

        $settings = app(Settings::class);
        $definition = $settings->definition('admin_branding');
        $definition
            ? $settings->saveFor($definition, $values)
            : $settings->save('admin_branding', $values, media: ['logo_light', 'logo_dark', 'favicon']);

        Notification::make()->success()->title(__('thememanager::theme.notifications.copied_to_admin'))->send();
    }

    protected function contrastHint(string $token, ?string $color, ?string $background): ?string
    {
        if (! in_array($token, self::CONTRAST_CHECKED, true) || ! Contrast::isHex($color) || ! Contrast::isHex($background)) {
            return null;
        }

        $ratio = Contrast::ratio($color, $background);

        return $ratio < Contrast::AA ? __('thememanager::theme.help.low_contrast', ['ratio' => $ratio]) : null;
    }

    /** @return array<string, string> */
    protected function fonts(): array
    {
        $current = array_intersect_key(app(DesignTokens::class)->values($this->theme()), array_flip(DesignTokens::FONTS));

        return collect([...DesignTokens::GOOGLE_FONTS, ...array_values($current)])->unique()->sort()
            ->mapWithKeys(fn (string $font): array => [$font => $font])->all();
    }

    /** theme.json "options" (Theme Guide §11). */
    protected function optionFields(): array
    {
        return collect($this->theme()->manifest()->options())->map(function (array $option) {
            $label = $option['label'] ?? Str::headline($option['key']);

            return match ($option['type']) {
                'toggle' => Toggle::make($option['key'])->label($label),
                'select' => Select::make($option['key'])->label($label)->selectablePlaceholder(false)
                    ->options(collect($option['options'] ?? [])->mapWithKeys(fn (string $v): array => [$v => Str::headline($v)])->all()),
                'number' => TextInput::make($option['key'])->label($label)->numeric(),
                'color' => ColorPicker::make($option['key'])->label($label)->hex(),
                'image' => MediaPicker::make($option['key'])->label($label)->image()->storeIds(),
                default => TextInput::make($option['key'])->label($label)->maxLength(255),
            };
        })->all();
    }

    protected function theme(): Theme
    {
        return app(Themes::class)->active();
    }
}
