<?php

namespace Modules\Menu\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;
use Modules\Menu\Support\MenuEditor;
use Modules\Menu\Support\MenuLinks;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Vocabularies;

/**
 * Appearance › Menus (C-08, UI/UX Part A §7.4): pick an owner and a location; a
 * place without its own menu shows where its menu comes from, with "create own",
 * "copy and edit" and the menu source; an own menu is edited as a tree (3 levels).
 */
class ManageMenus extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'menus';

    #[Url]
    public ?string $owner = null;

    #[Url]
    public string $location = 'primary';

    /** @var array<string, mixed> selectors */
    public ?array $pick = [];

    /** @var array<string, mixed> the tree */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return 'Appearance';
    }

    public static function getNavigationLabel(): string
    {
        return __('menu::menu.title');
    }

    public function getTitle(): string
    {
        return __('menu::menu.title');
    }

    public static function canAccess(): bool
    {
        return MenuEditor::ownerKeys(Auth::user()) !== [];
    }

    public function mount(): void
    {
        $owners = MenuEditor::ownerKeys(Auth::user());
        abort_if($owners === [], 403);
        $this->owner = in_array($this->owner, $owners, true) ? $this->owner : $owners[0];
        $this->location = array_key_exists($this->location, MenuEditor::locations()) ? $this->location : array_key_first(MenuEditor::locations());

        $this->pickForm->fill(['owner' => $this->owner, 'location' => $this->location, 'mode' => $this->editor()->mode($this->owner)]);
        $this->load();
    }

    public function pickForm(Schema $schema): Schema
    {
        return $schema->statePath('pick')->columns(3)->components([
            Select::make('owner')->label(__('platform::relations.owner'))
                ->options(fn (): array => MenuEditor::ownerOptions(Auth::user()))
                ->searchable()->selectablePlaceholder(false)->live()
                ->afterStateUpdated(fn (?string $state) => $this->switchTo(owner: $state)),
            Select::make('location')->label(__('menu::menu.location'))
                ->options(fn (): array => MenuEditor::locations())
                ->selectablePlaceholder(false)->live()
                ->afterStateUpdated(fn (?string $state) => $this->switchTo(location: $state)),
            // FR-MENU-08: where an entity's menus come from when it has none of its own
            Select::make('mode')->label(__('menu::menu.source'))
                ->options(['inherit' => __('menu::menu.modes.inherit'), 'main' => __('menu::menu.modes.main'), 'own' => __('menu::menu.modes.own')])
                ->selectablePlaceholder(false)->live()
                ->visible(fn (): bool => $this->owner !== RelationEngine::SITE_KEY)
                ->afterStateUpdated(function (?string $state): void {
                    $this->editor()->setMode($this->owner, $state ?? 'inherit');
                    Notification::make()->success()->title(__('menu::menu.notifications.source_saved'))->send();
                }),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            // FallbackIndicator (UI/UX Part A §6)
            Section::make()
                ->visible(fn (): bool => $this->ownMenu() === null)
                ->schema([
                    Text::make(fn (): string => $this->fallbackText()),
                    Actions::make([
                        Action::make('createOwn')->label(__('menu::menu.actions.create_own'))
                            ->icon(Heroicon::OutlinedPlus)
                            ->action(fn () => $this->createOwn(copy: false)),
                        Action::make('copyCurrent')->label(fn (): string => __('menu::menu.actions.copy', ['owner' => $this->currentOwnerLabel()]))
                            ->icon(Heroicon::OutlinedDocumentDuplicate)->color('gray')
                            ->visible(fn (): bool => $this->editor()->current($this->owner, $this->location)['menu'] instanceof Menu)
                            ->action(fn () => $this->createOwn(copy: true)),
                    ]),
                ]),
            Section::make(fn (): string => __('menu::menu.structure', ['location' => MenuEditor::locations()[$this->location] ?? $this->location]))
                ->visible(fn (): bool => $this->ownMenu() !== null)
                ->schema([
                    static::itemsRepeater('items', 1),
                ])
                ->footerActions([
                    Action::make('save')->label(__('menu::menu.actions.save'))->action(fn () => $this->save()),
                    Action::make('deleteOwn')->label(__('menu::menu.actions.delete_own'))
                        ->color('danger')->requiresConfirmation()
                        ->modalDescription(__('menu::menu.help.delete_own'))
                        ->action(fn () => $this->deleteOwn()),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedSchema::make('pickForm'), EmbeddedSchema::make('form')]);
    }

    /** Nested items, up to MenuItem::MAX_DEPTH levels (FR-MENU-02). */
    protected static function itemsRepeater(string $name, int $depth): Repeater
    {
        return Repeater::make($name)
            ->label($depth === 1 ? null : __('menu::menu.sub_items'))
            ->hiddenLabel($depth === 1)
            ->schema(static::itemFields($depth))
            ->itemLabel(fn (array $state): string => static::itemLabel($state))
            ->collapsible()->collapsed($depth > 1)
            ->reorderable()->cloneable()
            ->defaultItems(0)
            ->addActionLabel(__('menu::menu.actions.add_item'));
    }

    protected static function itemFields(int $depth): array
    {
        $links = app(MenuLinks::class);
        $is = fn (string ...$types): \Closure => fn (Get $get): bool => in_array($get('type'), $types, true);

        return [
            Hidden::make('id'),
            Grid::make(3)->schema([
                Select::make('type')->label(__('menu::menu.fields.type'))
                    ->options(collect(MenuItem::TYPES)->mapWithKeys(fn (string $t): array => [$t => __("menu::menu.types.{$t}")])->all())
                    ->default('record')->required()->live()->selectablePlaceholder(false),
                Select::make('record_type')->label(__('menu::menu.fields.record_type'))
                    ->options(fn (): array => $links->recordTypeLabels())
                    ->visible($is('record'))->required($is('record'))->live(),
                Select::make('record_id')->label(__('menu::menu.fields.record'))
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search, Get $get): array => $links->recordOptions($get('record_type'), $search))
                    ->getOptionLabelUsing(fn ($value, Get $get): ?string => $links->recordOptions($get('record_type'), ids: [(int) $value])[(int) $value] ?? null)
                    ->options(fn (Get $get): array => $links->recordOptions($get('record_type')))
                    ->visible($is('record'))->required($is('record')),
                Select::make('term_id')->label(__('menu::menu.fields.term'))
                    ->options(fn (): array => collect(app(Vocabularies::class)->definitions())
                        ->mapWithKeys(fn (array $d, string $key): array => [app(Vocabularies::class)->find($key)->name => app(Vocabularies::class)->options($key)])
                        ->filter()->all())
                    ->searchable()->visible($is('taxonomy'))->required($is('taxonomy')),
                MediaPicker::make('file_id')->label(__('menu::menu.fields.file'))->storeIds()
                    ->visible($is('file'))->required($is('file')),
                Select::make('route_key')->label(__('menu::menu.fields.module_page'))
                    ->options(fn (): array => $links->modulePages())
                    ->helperText(__('menu::menu.help.module_page'))
                    ->visible($is('module_page'))->required($is('module_page')),
                TextInput::make('url')->label(__('menu::menu.fields.url'))
                    ->regex('#^(https?://|/|mailto:|tel:)#')->maxLength(2000)
                    ->visible($is('url'))->required($is('url')),
            ]),
            // Inside repeaters: one field per language, not tabs
            Grid::make(2)->schema(TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                TextInput::make("title.{$locale}")->label(__('menu::menu.fields.title')." ({$language})")
                    ->helperText($isDefault ? __('menu::menu.help.title') : null)
                    ->required(fn (Get $get): bool => $isDefault && in_array($get('type'), ['url', 'heading', 'module_page'], true))
                    ->maxLength(150),
            ])),
            Grid::make(4)->schema([
                TextInput::make('icon')->label(__('menu::menu.fields.icon'))->maxLength(100),
                TextInput::make('css_class')->label(__('menu::menu.fields.css_class'))->maxLength(100),
                Toggle::make('open_in_new_tab')->label(__('menu::menu.fields.new_tab')),
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
            ]),
            ...($depth < MenuItem::MAX_DEPTH ? [static::itemsRepeater('children', $depth + 1)] : []),
        ];
    }

    /** Title, or the linked record; ⚠ when the website hides it (FR-MENU-10). */
    protected static function itemLabel(array $state): string
    {
        $default = collect((array) ($state['title'] ?? []))->filter()->first();
        $item = filled($state['id'] ?? null) ? MenuItem::query()->find($state['id']) : null;
        $label = $default ?: ($item ? app(MenuLinks::class)->label($item) : __('menu::menu.types.'.($state['type'] ?? 'url')));
        $problem = $item ? app(MenuLinks::class)->problem($item) : null;

        return $problem ? "⚠ {$label} — ".__("menu::menu.problems.{$problem}") : $label;
    }

    public function switchTo(?string $owner = null, ?string $location = null): void
    {
        if ($owner !== null && in_array($owner, MenuEditor::ownerKeys(Auth::user()), true)) {
            $this->owner = $owner;
            $this->pick['mode'] = $this->editor()->mode($owner);
        }

        if ($location !== null && array_key_exists($location, MenuEditor::locations())) {
            $this->location = $location;
        }

        $this->load();
    }

    protected function load(): void
    {
        $menu = $this->ownMenu();
        $this->form->fill(['items' => $menu ? $this->editor()->tree($menu) : []]);
    }

    /** Livewire-callable: the owner is always one of the user's (mount / switchTo). */
    public function createOwn(bool $copy = false): void
    {
        $this->guardOwner();
        $current = $copy ? $this->editor()->current($this->owner, $this->location)['menu'] : null;
        $this->editor()->create($this->owner, $this->location, $current instanceof Menu ? $current : null);
        $this->load();

        Notification::make()->success()->title(__('menu::menu.notifications.created'))->send();
    }

    public function save(): void
    {
        $this->guardOwner();
        $menu = $this->ownMenu() ?? abort(404);
        $this->editor()->save($menu, $this->form->getState()['items'] ?? []);
        $this->load();

        Notification::make()->success()->title(__('menu::menu.notifications.saved'))->send();
    }

    public function deleteOwn(): void
    {
        $this->guardOwner();
        if ($menu = $this->ownMenu()) {
            $this->editor()->delete($menu);
        }

        $this->load();
        Notification::make()->success()->title(__('menu::menu.notifications.deleted'))->send();
    }

    protected function guardOwner(): void
    {
        abort_unless(in_array($this->owner, MenuEditor::ownerKeys(Auth::user()), true), 403);
    }

    protected function ownMenu(): ?Menu
    {
        return $this->owner ? $this->editor()->own($this->owner, $this->location) : null;
    }

    protected function fallbackText(): string
    {
        $from = $this->editor()->current($this->owner, $this->location)['from'];

        return $from === null
            ? __('menu::menu.fallback.none')
            : __('menu::menu.fallback.uses', ['owner' => $this->editor()->ownerLabel($from)]);
    }

    protected function currentOwnerLabel(): string
    {
        $from = $this->editor()->current($this->owner, $this->location)['from'];

        return $from ? $this->editor()->ownerLabel($from) : '';
    }

    protected function editor(): MenuEditor
    {
        return app(MenuEditor::class);
    }
}
