<?php

namespace Modules\Statistics\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Platform\Context\FollowsWorkingIn;
use Platform\Context\MicrositeOwners;
use Platform\Context\MicrositeTool;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Localization\Languages;
use Platform\Scope\UserScopeFilter;
use Platform\Statistics\StatContext;
use Platform\Statistics\Statistics;
use Platform\Statistics\StatItem;
use Platform\Statistics\StatSet;

/**
 * Appearance › Statistics (C-20, UI/UX Part A §7.6): counters of the main site and
 * of every entity — manual numbers or module metrics counted for that entity and
 * everything below it, with override, rounding, prefix / suffix; live preview.
 */
class ManageStatistics extends Page implements MicrositeTool
{
    use FollowsWorkingIn;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 40;

    protected static ?string $slug = 'statistics';

    #[Url]
    public ?string $owner = null;

    #[Url]
    public string $set = 'default';

    public ?array $pick = [];

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return 'Appearance';
    }

    public static function getNavigationLabel(): string
    {
        return __('statistics::statistics.title');
    }

    public function getTitle(): string
    {
        return __('statistics::statistics.title');
    }

    /** Microsite tool (UI/UX Part A §4.1): the entity editors' "Microsite" button opens it on one owner. */
    public static function canUseFor(string $ownerKey): bool
    {
        return in_array($ownerKey, MicrositeOwners::keys(Auth::user(), 'Statistic'), true);
    }

    public static function urlFor(string $ownerKey): string
    {
        return static::getUrl(['owner' => $ownerKey]);
    }

    public static function isSetUpFor(string $ownerKey): bool
    {
        return app(Statistics::class)->set($ownerKey) !== null;
    }

    public static function canAccess(): bool
    {
        return MicrositeOwners::keys(Auth::user(), 'Statistic') !== [];
    }

    public function mount(): void
    {
        $owners = MicrositeOwners::keys(Auth::user(), 'Statistic');
        abort_if($owners === [], 403);
        $this->owner = $this->ownerOnOpen($owners);   // "Working in" unless asked for
        $this->set = Str::slug($this->set) ?: 'default';

        $this->pickForm->fill(['owner' => $this->owner, 'set' => $this->set]);
        $this->load();
    }

    public function pickForm(Schema $schema): Schema
    {
        return $schema->statePath('pick')->columns(2)->components([
            Select::make('owner')->label(__('platform::relations.owner'))
                ->options(fn (): array => MicrositeOwners::options(Auth::user(), 'Statistic'))
                ->searchable()->selectablePlaceholder(false)->live()
                ->afterStateUpdated(function (?string $state): void {
                    if (in_array($state, MicrositeOwners::keys(Auth::user(), 'Statistic'), true)) {
                        $this->owner = $state;
                        $this->set = 'default';
                        $this->pick['set'] = 'default';
                        $this->load();
                    }
                }),
            Select::make('set')->label(__('statistics::statistics.set'))
                ->options(fn (): array => $this->setOptions())
                ->selectablePlaceholder(false)->live()
                ->afterStateUpdated(function (?string $state): void {
                    $this->set = Str::slug((string) $state) ?: 'default';
                    $this->load();
                })
                ->suffixAction(Action::make('newSet')->icon(Heroicon::OutlinedPlus)
                    ->tooltip(__('statistics::statistics.actions.new_set'))
                    ->schema([TextInput::make('key')->label(__('statistics::statistics.fields.key'))->required()->alphaDash()->maxLength(50)])
                    ->action(function (array $data): void {
                        $this->set = Str::slug($data['key']);
                        $this->pick['set'] = $this->set;
                        $this->createSet();
                    })),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make()->visible(fn (): bool => $this->ownSet() === null)->schema([
                Text::make(__('statistics::statistics.empty')),
                Actions::make([
                    Action::make('createSet')->label(__('statistics::statistics.actions.create'))
                        ->icon(Heroicon::OutlinedPlus)->action(fn () => $this->createSet()),
                ]),
            ]),
            Section::make()->visible(fn (): bool => $this->ownSet() !== null)
                ->schema([
                    Grid::make(2)->schema([
                        ...TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                            TextInput::make("name.{$locale}")->label(__('statistics::statistics.fields.name')." ({$language})")
                                ->required($isDefault)->maxLength(100),
                        ]),
                    ]),
                    Toggle::make('is_active')->label(__('platform::relations.active')),
                    Repeater::make('items')->label(__('statistics::statistics.fields.items'))
                        ->schema($this->itemFields())
                        ->itemLabel(fn (array $state): string => $this->itemLabel($state))
                        ->reorderable()->collapsible()->cloneable()->defaultItems(0)
                        ->addActionLabel(__('statistics::statistics.actions.add_item')),
                ])
                ->footerActions([
                    Action::make('save')->label(__('statistics::statistics.actions.save'))->action(fn () => $this->save()),
                    Action::make('deleteSet')->label(__('statistics::statistics.actions.delete'))
                        ->color('danger')->requiresConfirmation()->action(fn () => $this->deleteSet()),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedSchema::make('pickForm'), EmbeddedSchema::make('form')]);
    }

    /** FR-STAT-02/06: label, manual or automatic, override, rounding, prefix / suffix, icon, as of. */
    protected function itemFields(): array
    {
        $is = fn (string $source): \Closure => fn (Get $get): bool => $get('source') === $source;

        return [
            Hidden::make('id'),
            Grid::make(2)->schema(TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                TextInput::make("label.{$locale}")->label(__('statistics::statistics.fields.label')." ({$language})")->required($isDefault)->maxLength(100),
            ])),
            Grid::make(3)->schema([
                Select::make('source')->label(__('statistics::statistics.fields.source'))
                    ->options(['manual' => __('statistics::statistics.sources.manual'), 'metric' => __('statistics::statistics.sources.metric')])
                    ->default('manual')->required()->live()->selectablePlaceholder(false),
                Select::make('metric_key')->label(__('statistics::statistics.fields.metric'))
                    ->options(fn (): array => app(Statistics::class)->metricLabels())
                    ->visible($is('metric'))->required($is('metric'))->live(),
                TextInput::make('manual_value')->label(__('statistics::statistics.fields.value'))
                    ->numeric()->integer()->minValue(0)->visible($is('manual'))->required($is('manual'))->live(onBlur: true),
                TextInput::make('override_value')->label(__('statistics::statistics.fields.override'))
                    ->numeric()->integer()->minValue(0)->helperText(__('statistics::statistics.help.override'))->live(onBlur: true),
            ]),
            Grid::make(5)->schema([
                Select::make('rounding')->label(__('statistics::statistics.fields.rounding'))
                    ->options(collect(StatItem::ROUNDINGS)->mapWithKeys(fn (string $r): array => [$r => __("statistics::statistics.roundings.{$r}")])->all())
                    ->default('none')->selectablePlaceholder(false)->live(),
                TextInput::make('prefix')->label(__('statistics::statistics.fields.prefix'))->maxLength(20)->live(onBlur: true),
                TextInput::make('suffix')->label(__('statistics::statistics.fields.suffix'))->maxLength(20)->live(onBlur: true),
                TextInput::make('icon')->label(__('statistics::statistics.fields.icon'))->maxLength(100),
                DatePicker::make('as_of')->label(__('statistics::statistics.fields.as_of')),
            ]),
            Grid::make(2)->schema([
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
                // Live value and how the website shows it
                Text::make(fn (Get $get): string => $this->preview($get)),
            ]),
        ];
    }

    protected function preview(Get $get): string
    {
        $item = new StatItem([
            'source' => $get('source') ?? 'manual', 'metric_key' => $get('metric_key'),
            'manual_value' => filled($get('manual_value')) ? (int) $get('manual_value') : null,
            'override_value' => filled($get('override_value')) ? (int) $get('override_value') : null,
            'rounding' => $get('rounding'), 'prefix' => $get('prefix'), 'suffix' => $get('suffix'), 'is_active' => true,
        ]);
        $stats = app(Statistics::class);

        if ($problem = $stats->problem($item)) {
            return '⚠ '.__("statistics::statistics.problems.{$problem}");
        }

        $value = $stats->value($item, StatContext::of($this->owner));

        return $value === null ? '—' : __('statistics::statistics.preview', ['value' => number_format($value), 'display' => $stats->display($item, $value, app(Languages::class)->default())]);
    }

    protected function itemLabel(array $state): string
    {
        $label = collect((array) ($state['label'] ?? []))->filter()->first() ?? __('statistics::statistics.fields.label');
        $item = new StatItem(['source' => $state['source'] ?? 'manual', 'metric_key' => $state['metric_key'] ?? null,
            'manual_value' => $state['manual_value'] ?? null, 'override_value' => $state['override_value'] ?? null, 'is_active' => (bool) ($state['is_active'] ?? true)]);
        $problem = app(Statistics::class)->problem($item);

        return $problem ? "⚠ {$label} — ".__("statistics::statistics.problems.{$problem}") : $label;
    }

    public function createSet(): void
    {
        $this->guardOwner();

        if ($this->ownSet() === null) {
            $set = (new StatSet(['key' => $this->set, 'name' => [app(Languages::class)->default() => Str::headline($this->set)]]))->setOwnerKey($this->owner);
            $set->save();
        }

        $this->load();
        Notification::make()->success()->title(__('statistics::statistics.notifications.created'))->send();
    }

    public function save(): void
    {
        $this->guardOwner();
        $set = $this->ownSet() ?? abort(404);
        $state = $this->form->getState();

        DB::transaction(function () use ($set, $state): void {
            $set->update(['name' => array_filter((array) $state['name'], fn ($t): bool => filled($t)), 'is_active' => (bool) ($state['is_active'] ?? true)]);
            $kept = [];

            foreach (array_values((array) ($state['items'] ?? [])) as $position => $data) {
                $item = (filled($data['id'] ?? null) ? $set->items()->find($data['id']) : null) ?? new StatItem(['stat_set_id' => $set->id]);
                $item->fill([
                    'label' => array_filter((array) ($data['label'] ?? []), fn ($t): bool => filled($t)),
                    'source' => $data['source'] ?? 'manual',
                    'metric_key' => ($data['source'] ?? null) === 'metric' ? $data['metric_key'] : null,
                    'manual_value' => ($data['source'] ?? null) === 'manual' && filled($data['manual_value'] ?? null) ? (int) $data['manual_value'] : null,
                    'override_value' => filled($data['override_value'] ?? null) ? (int) $data['override_value'] : null,
                    'rounding' => $data['rounding'] ?? 'none',
                    'prefix' => $data['prefix'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                    'icon' => $data['icon'] ?? null,
                    'as_of' => $data['as_of'] ?? null,
                    'is_active' => (bool) ($data['is_active'] ?? true),
                    'sort_order' => $position + 1,
                ]);
                $item->stat_set_id = $set->id;
                $item->save();
                $kept[] = $item->id;
            }

            $set->items()->whereNotIn('id', $kept)->get()->each->forceDelete();
        });

        $this->load();
        Notification::make()->success()->title(__('statistics::statistics.notifications.saved'))->send();
    }

    public function deleteSet(): void
    {
        $this->guardOwner();
        $this->ownSet()?->delete();
        $this->set = 'default';
        $this->pick['set'] = 'default';
        $this->load();
        Notification::make()->success()->title(__('statistics::statistics.notifications.deleted'))->send();
    }

    protected function load(): void
    {
        $set = $this->ownSet();

        $this->form->fill($set ? [
            'name' => $set->getTranslations('name'),
            'is_active' => $set->is_active,
            'items' => $set->items()->get()->map(fn (StatItem $item): array => [
                'id' => $item->id, 'label' => $item->getTranslations('label'), 'source' => $item->source, 'metric_key' => $item->metric_key,
                'manual_value' => $item->manual_value, 'override_value' => $item->override_value, 'rounding' => $item->rounding ?? 'none',
                'prefix' => $item->prefix, 'suffix' => $item->suffix, 'icon' => $item->icon, 'as_of' => $item->as_of?->toDateString(),
                'is_active' => $item->is_active,
            ])->all(),
        ] : []);
    }

    /** @return array<string, string> key => name, sets of this owner (and the one being created) */
    protected function setOptions(): array
    {
        $sets = StatSet::query()->withoutGlobalScope(UserScopeFilter::class)->ownedBy($this->owner)->get()
            ->mapWithKeys(fn (StatSet $set): array => [$set->key => (string) ($set->name ?: $set->key)])->all();

        return [...['default' => __('statistics::statistics.default_set')], ...$sets, ...[$this->set => $sets[$this->set] ?? Str::headline($this->set)]];
    }

    protected function ownSet(): ?StatSet
    {
        return $this->owner ? StatSet::query()->withoutGlobalScope(UserScopeFilter::class)->ownedBy($this->owner)->where('key', $this->set)->first() : null;
    }

    protected function guardOwner(): void
    {
        abort_unless(in_array($this->owner, MicrositeOwners::keys(Auth::user(), 'Statistic'), true), 403);
    }
}
