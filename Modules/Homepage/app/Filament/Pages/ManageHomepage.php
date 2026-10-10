<?php

namespace Modules\Homepage\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Modules\Homepage\Models\HomeSection;
use Modules\Homepage\Models\Layout;
use Modules\Homepage\Sections\CustomHtmlSection;
use Modules\Homepage\Support\Homepages;
use Platform\Context\FollowsWorkingIn;
use Platform\Context\MicrositeOwners;
use Platform\Context\MicrositeTool;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Homepage\SectionContext;
use Platform\Relations\RelationEngine;
use Platform\Scope\UserScopeFilter;

/**
 * Appearance › Homepage (C-10, UI/UX Part A §7.5): the main homepage and every
 * microsite's — sections in order (drag), each with heading, source mode and count
 * (content sections), its own settings, background and schedule (FR-HOME-01..11).
 */
class ManageHomepage extends Page implements MicrositeTool
{
    use FollowsWorkingIn;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'homepage';

    #[Url]
    public ?string $owner = null;

    public ?array $pick = [];

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return 'Appearance';
    }

    public static function getNavigationLabel(): string
    {
        return __('homepage::homepage.title');
    }

    public function getTitle(): string
    {
        return __('homepage::homepage.title');
    }

    /** Microsite tool (UI/UX Part A §4.1): the entity editors' "Microsite" button opens it on one owner. */
    public static function canUseFor(string $ownerKey): bool
    {
        return in_array($ownerKey, MicrositeOwners::keys(Auth::user(), 'Homepage'), true);
    }

    public static function urlFor(string $ownerKey): string
    {
        return static::getUrl(['owner' => $ownerKey]);
    }

    public static function isSetUpFor(string $ownerKey): bool
    {
        return app(Homepages::class)->own($ownerKey) !== null;
    }

    public static function canAccess(): bool
    {
        return MicrositeOwners::keys(Auth::user(), 'Homepage') !== [];
    }

    public function mount(): void
    {
        $owners = MicrositeOwners::keys(Auth::user(), 'Homepage');
        abort_if($owners === [], 403);
        $this->owner = $this->ownerOnOpen($owners);   // "Working in" unless asked for

        $this->pickForm->fill(['owner' => $this->owner]);
        $this->load();
    }

    public function pickForm(Schema $schema): Schema
    {
        return $schema->statePath('pick')->components([
            Select::make('owner')->label(__('platform::relations.owner'))
                ->options(fn (): array => MicrositeOwners::options(Auth::user(), 'Homepage'))
                ->searchable()->selectablePlaceholder(false)->live()
                ->afterStateUpdated(function (?string $state): void {
                    if (in_array($state, MicrositeOwners::keys(Auth::user(), 'Homepage'), true)) {
                        $this->owner = $state;
                        $this->load();
                    }
                }),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            // FR-HOME-08: without its own, a microsite shows the theme's default homepage
            Section::make()->visible(fn (): bool => $this->layout() === null)->schema([
                Text::make(__('homepage::homepage.empty')),
                Actions::make([
                    Action::make('createOwn')->label(__('homepage::homepage.actions.create'))
                        ->icon(Heroicon::OutlinedPlus)->action(fn () => $this->createOwn(false)),
                    Action::make('copyMain')->label(__('homepage::homepage.actions.copy_main'))
                        ->icon(Heroicon::OutlinedDocumentDuplicate)->color('gray')
                        ->visible(fn (): bool => $this->owner !== RelationEngine::SITE_KEY && app(Homepages::class)->own(RelationEngine::SITE_KEY) !== null)
                        ->action(fn () => $this->createOwn(true)),
                ]),
            ]),
            Section::make()->visible(fn (): bool => $this->layout() !== null)
                ->schema([
                    Toggle::make('is_active')->label(__('homepage::homepage.fields.layout_active'))
                        ->helperText(__('homepage::homepage.help.layout_active')),
                    Repeater::make('sections')->label(__('homepage::homepage.fields.sections'))
                        ->schema($this->sectionFields())
                        ->itemLabel(fn (array $state): string => $this->sectionLabel($state))
                        ->reorderable()->collapsible()->collapsed()->cloneable()->defaultItems(0)
                        ->addActionLabel(__('homepage::homepage.actions.add_section')),
                ])
                ->footerActions([
                    Action::make('save')->label(__('homepage::homepage.actions.save'))->action(fn () => $this->save()),
                    Action::make('deleteOwn')->label(__('homepage::homepage.actions.delete'))
                        ->color('danger')->requiresConfirmation()
                        ->modalDescription(__('homepage::homepage.help.delete'))
                        ->action(fn () => $this->deleteOwn()),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedSchema::make('pickForm'), EmbeddedSchema::make('form')]);
    }

    protected function sectionFields(): array
    {
        $homepages = app(Homepages::class);
        $class = fn (Get $get): ?string => $homepages->catalogue()[$get('section_type')] ?? null;
        $isContent = fn (Get $get): bool => ($c = $class($get)) !== null && $c::model() !== null;

        return [
            Hidden::make('id'),
            Select::make('section_type')->label(__('homepage::homepage.fields.type'))
                ->options(fn (): array => $homepages->catalogueOptions())
                // Custom HTML: Super Admins only
                ->disableOptionWhen(fn (string $value): bool => $value === CustomHtmlSection::key() && ! Auth::user()?->isSuperAdmin())
                ->required()->live()->searchable(),
            Grid::make(2)->schema(TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                TextInput::make("heading.{$locale}")->label(__('homepage::homepage.fields.heading')." ({$language})")->maxLength(150),
                TextInput::make("subheading.{$locale}")->label(__('homepage::homepage.fields.subheading')." ({$language})")->maxLength(255),
            ])),
            // FR-HOME-04/05/11: content sections
            Grid::make(4)->visible($isContent)->schema([
                Select::make('settings.source_mode')->label(__('homepage::homepage.fields.source_mode'))
                    ->options(collect(SectionContext::MODES)->mapWithKeys(fn (string $m): array => [$m => __("homepage::homepage.modes.{$m}")])->all())
                    ->default('highlighted_first')->selectablePlaceholder(false)->live(),
                TextInput::make('settings.count')->label(__('homepage::homepage.fields.count'))->numeric()->integer()->minValue(1)->maxValue(24)->default(6),
                TextInput::make('settings.layout')->label(__('homepage::homepage.fields.layout'))->placeholder('grid')->maxLength(50),
                Toggle::make('settings.include_descendants')->label(__('homepage::homepage.fields.include_descendants'))
                    ->visible(fn (): bool => $this->owner !== RelationEngine::SITE_KEY),
            ]),
            Select::make('settings.manual_ids')->label(__('homepage::homepage.fields.manual_ids'))
                ->multiple()->searchable()
                ->options(fn (Get $get): array => ($c = $class($get)) && $c::model()
                    ? app(RelationEngine::class)->for($this->owner)->content($c::model())->includeDescendants()->query()->limit(100)->get()
                        ->mapWithKeys(fn ($record): array => [$record->getKey() => (string) $record->getAttribute($record::$titleAttribute ?? 'name')])->all()
                    : [])
                ->visible(fn (Get $get): bool => $isContent($get) && $get('settings.source_mode') === 'manual'),
            TextInput::make('settings.view_all_url')->label(__('homepage::homepage.fields.view_all'))
                ->regex('#^(https?://|/)#')->maxLength(1000)->visible($isContent),
            // The section's own settings (slides, set, text …)
            Group::make(fn (Get $get): array => ($c = $class($get)) ? $c::settingsSchema() : [])->statePath('settings'),
            Grid::make(4)->schema([
                Select::make('settings.background')->label(__('homepage::homepage.fields.background'))
                    ->options(collect(['none', 'light', 'dark', 'primary'])->mapWithKeys(fn (string $b): array => [$b => __("homepage::homepage.backgrounds.{$b}")])->all())
                    ->default('none')->selectablePlaceholder(false),
                DateTimePicker::make('visible_from')->label(__('homepage::homepage.fields.visible_from'))->seconds(false),
                DateTimePicker::make('visible_until')->label(__('homepage::homepage.fields.visible_until'))->seconds(false)->after('visible_from'),
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
            ]),
        ];
    }

    /** "Latest News — Highlighted first · 6", ⚠ when hidden (FR-HOME-09, UI/UX §7.5). */
    protected function sectionLabel(array $state): string
    {
        $homepages = app(Homepages::class);
        $class = $homepages->catalogue()[$state['section_type'] ?? ''] ?? null;
        $label = $class ? $class::label() : (string) ($state['section_type'] ?? '');
        $heading = collect((array) ($state['heading'] ?? []))->filter()->first();
        $summary = $class && $class::model()
            ? ' — '.__('homepage::homepage.modes.'.($state['settings']['source_mode'] ?? 'highlighted_first')).' · '.($state['settings']['count'] ?? 6)
            : '';

        $problem = match (true) {
            $class === null && filled($state['section_type'] ?? null) => 'module_off',
            ! ($state['is_active'] ?? true) => 'inactive',
            default => null,
        };

        return ($problem ? '⚠ ' : '').$label.($heading ? " “{$heading}”" : '').$summary.($problem ? ' — '.__("homepage::homepage.problems.{$problem}") : '');
    }

    public function createOwn(bool $copyMain = false): void
    {
        $this->guardOwner();
        $homepages = app(Homepages::class);

        if ($homepages->own($this->owner) === null) {
            $homepages->create($this->owner, $copyMain ? $homepages->own(RelationEngine::SITE_KEY) : null);
        }

        $this->load();
        Notification::make()->success()->title(__('homepage::homepage.notifications.created'))->send();
    }

    public function save(): void
    {
        $this->guardOwner();
        $layout = $this->layout() ?? abort(404);
        $state = $this->form->getState();
        $superAdmin = (bool) Auth::user()?->isSuperAdmin();

        DB::transaction(function () use ($layout, $state, $superAdmin): void {
            $layout->update(['is_active' => (bool) ($state['is_active'] ?? true)]);
            $kept = [];

            foreach (array_values((array) ($state['sections'] ?? [])) as $position => $data) {
                $section = (filled($data['id'] ?? null) ? $layout->sections()->find($data['id']) : null) ?? new HomeSection(['layout_id' => $layout->id]);
                $settings = (array) ($data['settings'] ?? []);

                // Custom HTML stays as it was unless a Super Admin changed it
                if (($data['section_type'] ?? null) === CustomHtmlSection::key() && ! $superAdmin) {
                    $settings['html'] = $section->exists ? ($section->settings['html'] ?? null) : null;
                }

                $section->fill([
                    'section_type' => $data['section_type'],
                    'heading' => array_filter((array) ($data['heading'] ?? []), fn ($t): bool => filled($t)),
                    'subheading' => array_filter((array) ($data['subheading'] ?? []), fn ($t): bool => filled($t)),
                    'settings' => $settings,
                    'visible_from' => $data['visible_from'] ?? null,
                    'visible_until' => $data['visible_until'] ?? null,
                    'is_active' => (bool) ($data['is_active'] ?? true),
                    'sort_order' => $position + 1,
                ]);
                $section->layout_id = $layout->id;
                $section->save();
                $kept[] = $section->id;
            }

            $layout->sections()->whereNotIn('id', $kept)->get()->each->forceDelete();
        });

        Homepages::flush();
        $this->load();
        Notification::make()->success()->title(__('homepage::homepage.notifications.saved'))->send();
    }

    public function deleteOwn(): void
    {
        $this->guardOwner();
        $this->layout()?->delete();
        Homepages::flush();
        $this->load();
        Notification::make()->success()->title(__('homepage::homepage.notifications.deleted'))->send();
    }

    protected function load(): void
    {
        $layout = $this->layout();

        $this->form->fill($layout ? [
            'is_active' => $layout->is_active,
            'sections' => $layout->sections()->get()->map(fn (HomeSection $s): array => [
                'id' => $s->id,
                'section_type' => $s->section_type,
                'heading' => $s->getTranslations('heading'),
                'subheading' => $s->getTranslations('subheading'),
                'settings' => (array) $s->settings,
                'visible_from' => $s->visible_from?->toDateTimeString(),
                'visible_until' => $s->visible_until?->toDateTimeString(),
                'is_active' => $s->is_active,
            ])->all(),
        ] : []);
    }

    protected function layout(): ?Layout
    {
        return $this->owner ? Layout::query()->withoutGlobalScope(UserScopeFilter::class)->ownedBy($this->owner)->first() : null;
    }

    protected function guardOwner(): void
    {
        abort_unless(in_array($this->owner, MicrositeOwners::keys(Auth::user(), 'Homepage'), true), 403);
    }
}
