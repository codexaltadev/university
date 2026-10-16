<?php

namespace Modules\News\Filament\Resources\Posts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\News\Models\Post;
use Platform\Filament\Tables\ContentFilters;
use Platform\Taxonomy\Vocabularies;

/** News, newest first: picture, title, where shown, category, author, active, show-from (scheduled marked), views. */
class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['contentRelations', 'terms', 'authorUser'])->withViewTotal())
            ->defaultSort('active_at', 'desc')
            ->columns([
                ImageColumn::make('featured_image')
                    ->label(__('news::news.fields.featured_image'))
                    ->state(fn (Post $record): ?string => $record->featuredImage()?->previewUrl())
                    ->square(),
                TextColumn::make('title')->label(__('news::news.fields.title'))
                    ->searchable()->wrap()->limit(90)->weight('medium'),
                TextColumn::make('shown_in')->label(__('platform::relations.show_in'))
                    ->state(fn (Post $record): array => ContentFilters::placesOf($record))
                    ->badge()->color('gray')->limitList(2)->expandableLimitedList(),
                TextColumn::make('category')->label(__('news::news.fields.category'))
                    ->state(fn (Post $record): array => $record->terms
                        ->where('vocabulary_id', app(Vocabularies::class)->find(Post::CATEGORY)?->id)
                        ->pluck('name')->all())
                    ->badge()->color('info'),
                TextColumn::make('author')->label(__('news::news.fields.author'))
                    ->state(fn (Post $record): ?string => $record->authorName())
                    ->toggleable(),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
                TextColumn::make('active_at')->label(__('platform::relations.active_at'))
                    ->dateTime('M j, Y H:i')->sortable()
                    ->description(fn (Post $record): ?string => $record->isScheduled() ? __('news::news.scheduled') : null),
                TextColumn::make('view_total')->label(__('news::news.fields.views'))->numeric()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                ContentFilters::shownIn(),
                ContentFilters::term('category', Post::CATEGORY),
                ContentFilters::mainSite(),
                TernaryFilter::make('is_active')->label(__('platform::relations.active')),
                TrashedFilter::make(),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->recordActions([
                EditAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
