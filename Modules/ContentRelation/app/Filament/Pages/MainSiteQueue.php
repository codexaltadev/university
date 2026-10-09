<?php

namespace Modules\ContentRelation\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Platform\Relations\MainSiteQueue as Queue;
use Platform\Relations\RelationRejected;
use Platform\Relations\Targets;

/**
 * Content › Main Site Queue (FR-CNT-H08/H09, UI/UX Part A §7.3): entity content not on
 * the main site; requested first. Publish or dismiss, one by one or in bulk.
 * For users who may publish at least one content type to the main site (PublishMain).
 */
class MainSiteQueue extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?int $navigationSort = 900;

    protected static ?string $slug = 'main-site-queue';

    public static function getNavigationGroup(): ?string
    {
        return 'Content';
    }

    public static function getNavigationLabel(): string
    {
        return __('contentrelation::relation.queue.title');
    }

    public function getTitle(): string
    {
        return __('contentrelation::relation.queue.title');
    }

    public function getSubheading(): ?string
    {
        return __('contentrelation::relation.queue.subheading');
    }

    /** Pending requests waiting for a decision. */
    public static function getNavigationBadge(): ?string
    {
        $count = app(Queue::class)->pendingCount(Auth::user());

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function canAccess(): bool
    {
        return app(Queue::class)->types(Auth::user()) !== [];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        $queue = app(Queue::class);

        return $table
            ->records(function (?string $search, array $filters, int $page, int $recordsPerPage) use ($queue): LengthAwarePaginator {
                $items = $queue->items(Auth::user(), [
                    'type' => $filters['type']['value'] ?? null,
                    'place' => $filters['place']['value'] ?? null,
                    'days' => $filters['days']['value'] ?? null,
                ], $search);

                return new LengthAwarePaginator($items->forPage($page, $recordsPerPage), $items->count(), $recordsPerPage, $page);
            })
            ->resolveSelectedRecordsUsing(fn (array $keys): Collection => $queue->items(Auth::user())->only($keys))
            ->searchable()
            ->paginated([25, 50, 100])
            ->emptyStateHeading(__('contentrelation::relation.queue.empty'))
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle)
            ->columns([
                TextColumn::make('title')->label(__('contentrelation::relation.fields.content'))
                    ->description(fn (array $record): string => $record['type_label'])
                    ->url(fn (array $record): ?string => $record['url'])
                    ->weight('medium')->wrap(),
                TextColumn::make('places')->label(__('contentrelation::relation.fields.shown_in'))->wrap(),
                TextColumn::make('shown_at')->label(__('contentrelation::relation.fields.added'))->date('d M Y'),
                IconColumn::make('requested')->label(__('contentrelation::relation.fields.requested'))
                    ->boolean()->trueIcon(Heroicon::Star)->trueColor('warning')->falseIcon(null)
                    ->tooltip(fn (array $record): ?string => $record['requested_by'] ? __('contentrelation::relation.queue.requested_by', ['name' => $record['requested_by']]) : null),
            ])
            ->filters([
                SelectFilter::make('type')->label(__('contentrelation::relation.fields.type'))
                    ->options(fn (): array => collect($queue->types(Auth::user()))->map(fn (string $model): string => class_basename($model))->all()),
                SelectFilter::make('place')->label(__('contentrelation::relation.fields.shown_in'))
                    ->options(fn (): array => app(Targets::class)->options(Auth::user()))->searchable(),
                SelectFilter::make('days')->label(__('contentrelation::relation.fields.period'))
                    ->options([7 => __('contentrelation::relation.days', ['count' => 7]), 30 => __('contentrelation::relation.days', ['count' => 30]), 90 => __('contentrelation::relation.days', ['count' => 90])])
                    ->default(30),
            ])
            ->recordActions([
                Action::make('publish')->label(__('contentrelation::relation.actions.publish'))
                    ->icon(Heroicon::OutlinedGlobeAlt)->color('success')
                    ->action(fn (array $record) => $this->decide([$record['key']], 'publish')),
                Action::make('dismiss')->label(__('contentrelation::relation.actions.dismiss'))
                    ->icon(Heroicon::OutlinedXMark)->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription(__('contentrelation::relation.queue.dismiss_help'))
                    ->action(fn (array $record) => $this->decide([$record['key']], 'dismiss')),
            ])
            ->toolbarActions([
                BulkAction::make('publishSelected')->label(__('contentrelation::relation.actions.publish'))
                    ->icon(Heroicon::OutlinedGlobeAlt)->color('success')
                    ->action(fn (Collection $records) => $this->decide($records->pluck('key')->all(), 'publish'))
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('dismissSelected')->label(__('contentrelation::relation.actions.dismiss'))
                    ->icon(Heroicon::OutlinedXMark)->color('gray')->requiresConfirmation()
                    ->action(fn (Collection $records) => $this->decide($records->pluck('key')->all(), 'dismiss'))
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    /** @param list<string> $keys */
    protected function decide(array $keys, string $decision): void
    {
        $queue = app(Queue::class);
        $done = 0;

        foreach ($keys as $key) {
            try {
                $decision === 'publish' ? $queue->publish($key, Auth::user()) : $queue->dismiss($key, Auth::user());
                $done++;
            } catch (RelationRejected $e) {
                Notification::make()->danger()->title(implode(' ', $e->errors))->send();
            }
        }

        if ($done > 0) {
            Notification::make()->success()
                ->title(__("contentrelation::relation.notifications.{$decision}", ['count' => $done]))
                ->send();
        }

        $this->resetTable();
    }
}
