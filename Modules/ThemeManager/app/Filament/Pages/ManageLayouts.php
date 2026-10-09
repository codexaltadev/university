<?php

namespace Modules\ThemeManager\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Platform\Theme\LayoutResolver;
use Platform\Theme\Themes;

/**
 * Appearance › Layouts (C-06, FR-THEME-08/09, UI/UX Part A §7.11): for every module
 * page (News listing, FAQ listing …) one of the layouts the module and the active
 * theme offer, with options (items per row, sidebar). Data is untouched (AC-THEME-02).
 */
class ManageLayouts extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 52;

    protected static ?string $slug = 'layouts';

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return 'Appearance';
    }

    public static function getNavigationLabel(): string
    {
        return __('thememanager::theme.layouts.title');
    }

    public function getTitle(): string
    {
        return __('thememanager::theme.layouts.title');
    }

    public static function canAccess(): bool
    {
        return Themes::allows(Auth::user(), 'Customize:Theme');
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $layouts = $this->layouts();
        $data = [];

        foreach (array_keys($layouts->pages()) as $page) {
            $data[self::field($page)] = ['layout' => $layouts->for($page), ...$this->withDefaults($layouts->options($page))];
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        $pages = $this->layouts()->pages();

        if ($pages === []) {
            return $schema->components([Text::make(__('thememanager::theme.layouts.none'))]);
        }

        return $schema->statePath('data')->components(collect($pages)->map(fn (array $info, string $page) => Section::make(self::label($page))
            ->description(__('thememanager::theme.layouts.default', ['layout' => Str::headline((string) $this->layouts()->default($page))]))
            ->statePath(self::field($page))
            ->columns(3)
            ->schema([
                Select::make('layout')->label(__('thememanager::theme.layouts.layout'))
                    ->options(collect($info['layouts'])->mapWithKeys(fn (string $l): array => [$l => Str::headline($l)])->all())
                    ->required()->selectablePlaceholder(false),
                TextInput::make('per_row')->label(__('thememanager::theme.layouts.per_row'))->numeric()->minValue(1)->maxValue(6),
                Toggle::make('sidebar')->label(__('thememanager::theme.layouts.sidebar'))->inline(false),
            ]))->values()->all());
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label(__('thememanager::theme.actions.save'))->submit('save')->keyBindings(['mod+s'])
                            ->visible(fn (): bool => $this->layouts()->pages() !== []),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $state = $this->form->getState();
        $layouts = $this->layouts();

        foreach ($layouts->pages() as $page => $info) {
            $values = (array) ($state[self::field($page)] ?? []);

            if (! in_array($values['layout'] ?? null, $info['layouts'], true)) {
                continue;
            }

            $layouts->choose($page, $values['layout'], [
                'per_row' => filled($values['per_row'] ?? null) ? (int) $values['per_row'] : null,
                'sidebar' => (bool) ($values['sidebar'] ?? false),
            ]);
        }

        Notification::make()->success()->title(__('thememanager::theme.notifications.saved'))->send();
    }

    /** "faq.listing" → "faq__listing" (dots would nest the form state). */
    public static function field(string $page): string
    {
        return str_replace('.', '__', $page);
    }

    public static function label(string $page): string
    {
        [$module, $name] = array_pad(explode('.', $page, 2), 2, '');
        $key = "thememanager::theme.layouts.pages.{$name}";

        return Str::headline($module).' — '.(Lang::has($key) ? __($key) : Str::headline($name));
    }

    /** @param array<string, mixed> $options */
    protected function withDefaults(array $options): array
    {
        return ['per_row' => $options['per_row'] ?? null, 'sidebar' => (bool) ($options['sidebar'] ?? false)];
    }

    protected function layouts(): LayoutResolver
    {
        return app(LayoutResolver::class);
    }
}
