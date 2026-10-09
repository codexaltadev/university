<?php

namespace Modules\ContentRelation\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Platform\Registry\Registry;
use Platform\Relations\ContentRelation;
use Platform\Relations\RelationEngine;
use Platform\Relations\Targets;
use Platform\Support\MorphMap;

/**
 * Content › Highlights (FR-CNT-H06, UI/UX Part A §7.3): what is highlighted in one
 * place right now, in order; drag to reorder, take items off. The main site needs
 * HighlightMain on some content type; entity places follow the user's scope.
 */
class Highlights extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?int $navigationSort = 901;

    protected static ?string $slug = 'highlights';

    /** The place: "site:0" or an entity key. */
    #[Url]
    public string $context = RelationEngine::SITE_KEY;

    public static function getNavigationGroup(): ?string
    {
        return 'Content';
    }

    public static function getNavigationLabel(): string
    {
        return __('contentrelation::relation.highlights.title');
    }

    public function getTitle(): string
    {
        return __('contentrelation::relation.highlights.title');
    }

    public static function canAccess(): bool
    {
        return static::places(Auth::user()) !== [];
    }

    public function mount(): void
    {
        $places = static::places(Auth::user());

        if (! static::placeAllowed($places, $this->context)) {
            $this->context = RelationEngine::SITE_KEY;
        }

        $this->form->fill(['context' => $this->context]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('context')->label(__('contentrelation::relation.highlights.place'))
                ->options(fn (): array => static::places(Auth::user()))
                ->searchable()
                ->selectablePlaceholder(false)
                ->live()
                ->afterStateUpdated(function (?string $state): void {
                    if ($state && static::placeAllowed(static::places(Auth::user()), $state)) {
                        $this->context = $state;
                        $this->resetTable();
                    }
                }),
        ])->statePath('placeForm');
    }

    /** @var array<string, mixed> */
    public ?array $placeForm = [];

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedSchema::make('form'),
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        [$type, $id] = explode(':', $this->context, 2);

        return $table
            ->query(fn (): Builder => ContentRelation::query()
                ->with('content')
                ->where('target_type', $type)->where('target_id', (int) $id)
                ->whereIn('content_type', array_keys(static::attachableTypes()))
                ->highlightedNow())
            ->defaultSort('highlight_order')
            ->reorderable('highlight_order', fn (): bool => $this->mayEdit())
            ->paginated(false)
            ->emptyStateHeading(__('contentrelation::relation.highlights.empty'))
            ->emptyStateDescription(__('contentrelation::relation.highlights.empty_help'))
            ->columns([
                TextColumn::make('highlight_order')->label('#')->width('3rem'),
                TextColumn::make('title')->label(__('contentrelation::relation.fields.content'))
                    ->state(fn (ContentRelation $record): string => static::titleOf($record))
                    ->description(fn (ContentRelation $record): string => Str::headline(class_basename(static::attachableTypes()[$record->content_type] ?? $record->content_type)))
                    ->weight('medium')->wrap(),
                TextColumn::make('highlight_until')->label(__('platform::relations.until'))
                    ->date('d M Y')->placeholder('—'),
            ])
            ->recordActions([
                Action::make('unhighlight')->label(__('contentrelation::relation.actions.unhighlight'))
                    ->icon(Heroicon::OutlinedXMark)->color('gray')
                    ->visible(fn (): bool => $this->mayEdit())
                    ->requiresConfirmation()
                    ->modalDescription(__('contentrelation::relation.highlights.unhighlight_help'))
                    ->action(function (ContentRelation $record): void {
                        app(RelationEngine::class)->unhighlight($record);
                        Notification::make()->success()->title(__('contentrelation::relation.notifications.unhighlighted'))->send();
                    }),
            ]);
    }

    /** Drag & drop goes through the Relation Engine (order 1, 2, 3 … and cache events). */
    public function reorderTable(array $order, int|string|null $draggedRecordKey = null): void
    {
        if ($this->mayEdit()) {
            app(RelationEngine::class)->reorderHighlights($this->context, $order);
        }
    }

    protected function mayEdit(): bool
    {
        return static::placeAllowed(static::places(Auth::user()), $this->context);
    }

    /**
     * For Admins (UI/UX Part A §3): users who may highlight on the main site
     * (HighlightMain on any content type). Places: the main site, then the
     * entities of their scope, grouped by kind.
     *
     * @return array<string, string|array<string, string>> select options
     */
    public static function places(?User $user): array
    {
        $engine = app(RelationEngine::class);
        $mayMain = $user !== null && collect(static::attachableTypes())
            ->contains(fn (string $model): bool => $engine->mayHighlightIn($user, new $model, RelationEngine::SITE_KEY));

        if (! $mayMain) {
            return [];
        }

        return [RelationEngine::SITE_KEY => __('platform::relations.main_site'), ...app(Targets::class)->options($user)];
    }

    /** @return array<string, class-string> alias => model */
    protected static function attachableTypes(): array
    {
        return collect(app(Registry::class)->all('attachable'))
            ->mapWithKeys(fn (array $entry): array => [MorphMap::alias($entry['model']) => $entry['model']])
            ->all();
    }

    protected static function placeAllowed(array $places, string $context): bool
    {
        return isset($places[$context]) || collect($places)->contains(fn ($v): bool => is_array($v) && isset($v[$context]));
    }

    protected static function titleOf(ContentRelation $relation): string
    {
        $content = $relation->content;

        return $content ? (string) ($content->getAttribute($content::$titleAttribute ?? 'name') ?? '#'.$content->getKey()) : '#'.$relation->content_id;
    }
}
