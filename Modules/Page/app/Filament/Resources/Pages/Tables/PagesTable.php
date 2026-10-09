<?php

namespace Modules\Page\Filament\Resources\Pages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Page\Filament\Resources\Pages\PageResource;
use Modules\Page\Models\Page;
use Platform\Filament\Tables\ContentFilters;
use Platform\Relations\Targets;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('parent'))
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title')->label(__('page::page.fields.title'))
                    ->description(fn (Page $record): string => $record->url())
                    ->searchable(query: fn (Builder $query, string $search) => $query->where('title', 'like', "%{$search}%"))
                    ->weight('medium')->wrap(),
                TextColumn::make('owner')->label(__('platform::relations.owner'))
                    ->state(fn (Page $record): string => $record->isMainSiteOwned() ? '🌐 '.__('platform::relations.main_site') : app(Targets::class)->label($record->ownerKey()))
                    ->badge()->color('gray'),
                TextColumn::make('template')->label(__('page::page.fields.template'))
                    ->formatStateUsing(fn (string $state): string => __("page::page.templates.{$state}"))
                    ->toggleable(),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
                TextColumn::make('published_at')->label(__('platform::relations.published_at'))->date('d M Y')->sortable()
                    ->description(fn (Page $record): ?string => $record->published_at?->isFuture() ? __('platform::relations.scheduled') : null),
            ])
            ->filters([
                ContentFilters::owner(),
                SelectFilter::make('template')->label(__('page::page.fields.template'))
                    ->options(collect(Page::TEMPLATES)->mapWithKeys(fn (string $t): array => [$t => __("page::page.templates.{$t}")])->all()),
                TernaryFilter::make('is_active')->label(__('platform::relations.active')),
                TrashedFilter::make(),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->recordActions([
                EditAction::make(),
                static::duplicate(),
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

    /** FR-PAGE-08: a copy, inactive, "(copy)" in every title, same files. */
    public static function duplicate(): ReplicateAction
    {
        return ReplicateAction::make()
            ->label(__('page::page.actions.duplicate'))
            ->excludeAttributes(['sort_order', 'active_key'])
            ->beforeReplicaSaved(function (Page $replica): void {
                $replica->setTranslations('title', collect($replica->getTranslations('title'))
                    ->map(fn (string $title): string => $title.' '.__('page::page.copy_suffix'))->all());
                $replica->is_active = false;
            })
            ->after(function (Page $record, Page $replica): void {
                foreach (['featured_image', 'attachments'] as $field) {
                    $replica->syncMedia($field, $record->mediaIdsIn($field));
                }
            })
            ->successRedirectUrl(fn (Page $replica): string => PageResource::getUrl('edit', ['record' => $replica]));
    }
}
