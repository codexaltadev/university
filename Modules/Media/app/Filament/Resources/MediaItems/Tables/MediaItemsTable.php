<?php

namespace Modules\Media\Filament\Resources\MediaItems\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Modules\Media\Filament\Resources\MediaItems\MediaActions;
use Platform\Filament\Tables\ContentFilters;
use Platform\Media\MediaFolder;
use Platform\Media\MediaItem;

class MediaItemsTable
{
    public static function configure(Table $table): Table
    {
        $livewire = $table->getLivewire();
        $grid = ($livewire->display ?? 'grid') === 'grid';
        $perRow = (int) ($livewire->perRow ?? 5);

        return $table
            ->defaultSort('created_at', 'desc')
            // Explorer: only the open folder's files, unless "All files" or a search is on
            ->modifyQueryUsing(fn (Builder $query, $livewire): Builder => method_exists($livewire, 'isBrowsingFolder') && $livewire->isBrowsingFolder()
                ? $query->where('folder_id', $livewire->folder)
                : $query)
            ->columns($grid ? static::gridColumns() : static::listColumns())
            // The chosen cards per row on wide screens, fewer on small ones
            ->contentGrid($grid ? ['default' => 2, 'sm' => min(3, $perRow), 'lg' => min(4, $perRow), 'xl' => $perRow] : null)
            ->paginated($grid ? [24, 48, 96] : [20, 40, 80])
            ->filters(static::filters())
            ->recordActions($grid ? static::iconActions() : static::rowActions())
            ->toolbarActions([
                BulkActionGroup::make([
                    MediaActions::moveToFolder(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Grid: the same actions as icons; the page's grid styles float them over the
     * picture and show them on hover. Clicking the card opens the details.
     *
     * @return list<Action>
     */
    protected static function iconActions(): array
    {
        return array_map(
            fn (Action $action): Action => $action->iconButton()->tooltip(fn (Action $action): string => (string) $action->getLabel()),
            static::rowActions(),
        );
    }

    /** @return list<Action> */
    protected static function rowActions(): array
    {
        return [
            EditAction::make()->label(__('media::media.actions.details')),
            MediaActions::download(),
            MediaActions::replace(),
            DeleteAction::make()->modalDescription(fn (MediaItem $record) => MediaActions::deleteWarning($record)),
            RestoreAction::make(),
        ];
    }

    /**
     * FR-MEDIA-01/02: square picture filling the card (documents show their type),
     * one line of name, one line of size · folder — every card the same height.
     */
    protected static function gridColumns(): array
    {
        return [
            Stack::make([
                ImageColumn::make('preview')
                    ->state(fn (MediaItem $record): ?string => $record->kind === 'image' ? $record->url('medium') : null)
                    ->defaultImageUrl(fn (MediaItem $record): string => static::typeTile($record))
                    ->extraAttributes(['class' => 'uwms-media-thumb'])
                    ->extraImgAttributes(fn (MediaItem $record): array => [
                        'loading' => 'lazy',
                        'alt' => (string) $record->alt_text,
                        // Logos and drawings stay whole; photos fill the square
                        'class' => $record->kind !== 'image' || in_array($record->mime_type, ['image/svg+xml', 'image/gif'], true) ? 'uwms-contain' : '',
                    ]),
                static::titleColumn()->weight('medium')
                    ->tooltip(fn (MediaItem $record): string => (string) ($record->title ?: $record->fileName()))
                    ->extraAttributes(['class' => 'uwms-media-line']),
                TextColumn::make('facts')
                    ->state(fn (MediaItem $record): string => implode(' · ', array_filter([
                        Number::fileSize($record->size_bytes, 1),
                        $record->folder ? '📁 '.$record->folder->name : null,
                        $record->usage_count === 0 ? __('media::media.unused') : null,
                    ])))
                    ->color('gray')->size('xs')
                    ->extraAttributes(['class' => 'uwms-media-line']),
            ])->space(1),
        ];
    }

    /** A square SVG with the file type (PDF, DOCX …) for documents. */
    protected static function typeTile(MediaItem $record): string
    {
        $type = e(strtoupper(pathinfo((string) $record->fileName(), PATHINFO_EXTENSION)) ?: '?');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" rx="8" fill="#e2e8f0"/>'
            .'<path d="M34 22h22l12 12v44H34z" fill="#fff" stroke="#94a3b8" stroke-width="2"/>'
            .'<text x="50" y="64" font-family="sans-serif" font-size="13" font-weight="700" text-anchor="middle" fill="#475569">'.$type.'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    protected static function listColumns(): array
    {
        return [
            ImageColumn::make('preview')->label('')
                ->state(fn (MediaItem $record): ?string => $record->previewUrl())
                ->square()->imageSize(40)
                ->extraImgAttributes(['loading' => 'lazy', 'style' => 'object-fit: cover; border-radius: .25rem;']),
            static::titleColumn()->label(__('media::media.fields.title'))
                ->description(fn (MediaItem $record): ?string => $record->fileName()),
            TextColumn::make('kind')->label(__('media::media.fields.kind'))->badge()->color('gray')
                ->formatStateUsing(fn (string $state): string => __("media::media.kinds.{$state}")),
            TextColumn::make('size_bytes')->label(__('media::media.fields.size'))->sortable()
                ->formatStateUsing(fn (int $state): string => Number::fileSize($state, 1)),
            TextColumn::make('folder.name')->label(__('media::media.fields.folder'))
                ->icon(Heroicon::Folder)->iconColor('warning')->placeholder('—'),
            TextColumn::make('usage_count')->label(__('media::media.fields.used'))->sortable()->numeric(),
            TextColumn::make('creator.name')->label(__('media::media.fields.uploader')),
            TextColumn::make('created_at')->label(__('media::media.fields.uploaded'))->date('d M Y')->sortable(),
        ];
    }

    /** Search by title or file name (FR-MEDIA-05). */
    protected static function titleColumn(): TextColumn
    {
        return TextColumn::make('title')
            ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(fn (Builder $q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhereHas('media', fn (Builder $m) => $m->where('file_name', 'like', "%{$search}%"))))
            ->limit(40);
    }

    /** FR-MEDIA-05 / 15: type, month, uploader, folder, scope, unused, shared, trash. */
    protected static function filters(): array
    {
        return [
            SelectFilter::make('kind')->label(__('media::media.fields.kind'))
                ->options(collect(MediaItem::KINDS)->mapWithKeys(fn (string $k): array => [$k => __("media::media.kinds.{$k}")])->all()),
            SelectFilter::make('folder_id')->label(__('media::media.fields.folder'))
                ->options(fn (): array => MediaFolder::options(Auth::user())),
            SelectFilter::make('created_by')->label(__('media::media.fields.uploader'))
                ->relationship('creator', 'name')->searchable()->preload(),
            SelectFilter::make('scope')->label(__('media::media.fields.scope'))
                ->options(fn (): array => static::scopeOptions())
                ->query(function (Builder $query, array $data): Builder {
                    if (blank($data['value'] ?? null)) {
                        return $query;
                    }
                    [$type, $id] = explode(':', $data['value'], 2);

                    return $query->where('scope_type', $type)->where('scope_id', (int) $id);
                }),
            Filter::make('uploaded')
                ->schema([
                    Grid::make(2)->schema([
                        DatePicker::make('from')->label(__('media::media.fields.from')),
                        DatePicker::make('until')->label(__('media::media.fields.until')),
                    ]),
                ])
                ->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('core_media_items.created_at', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('core_media_items.created_at', '<=', $date))),
            Filter::make('unused')->label(__('media::media.filters.unused'))->toggle()
                ->query(fn (Builder $query): Builder => $query->where('usage_count', 0)),
            TernaryFilter::make('is_shared')->label(__('media::media.fields.shared')),
            ContentFilters::missingTranslation('alt_text'),
            TrashedFilter::make(),
        ];
    }

    /** @return array<string, string> "type:id" => entity name, from the scopes in the library */
    protected static function scopeOptions(): array
    {
        return MediaItem::query()->visibleTo(Auth::user())
            ->select(['scope_type', 'scope_id'])->distinct()->get()
            ->mapWithKeys(function (MediaItem $row): array {
                $key = "{$row->scope_type}:{$row->scope_id}";

                if ($row->scope_type === 'site') {
                    return [$key => __('media::media.main_site')];
                }

                $class = Relation::getMorphedModel($row->scope_type);
                $entity = $class ? $class::query()->find($row->scope_id) : null;

                return [$key => $entity?->getAttribute($class::$titleAttribute ?? 'name') ?? $key];
            })
            ->all();
    }
}
