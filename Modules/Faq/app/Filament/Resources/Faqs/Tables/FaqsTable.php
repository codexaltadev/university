<?php

namespace Modules\Faq\Filament\Resources\Faqs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Faq\Models\Faq;
use Platform\Filament\Tables\ContentFilters;
use Platform\Taxonomy\Vocabularies;

/**
 * FAQ list (UI/UX Part A §5.1). Hundreds of questions across faculties, departments,
 * programs and offices: filter by where they are shown, main site, category, state.
 */
class FaqsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['contentRelations', 'terms']))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('question')->label(__('faq::faq.fields.question'))
                    ->searchable()->wrap()->limit(120)->weight('medium'),
                TextColumn::make('shown_in')->label(__('platform::relations.show_in'))
                    ->state(fn (Faq $record): array => ContentFilters::placesOf($record))
                    ->badge()->color('gray')->limitList(3)->expandableLimitedList(),
                TextColumn::make('category')->label(__('faq::faq.fields.category'))
                    // From the eager-loaded terms: no query per row
                    ->state(fn (Faq $record): array => $record->terms
                        ->where('vocabulary_id', app(Vocabularies::class)->find('faq.category')->id)
                        ->pluck('name')->all())
                    ->badge()->color('info'),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
                TextColumn::make('helpful_yes')->label(__('faq::faq.fields.helpful'))->sortable()
                    ->formatStateUsing(fn (Faq $record): string => "👍 {$record->helpful_yes} · 👎 {$record->helpful_no}")
                    ->toggleable(),
                TextColumn::make('updated_at')->label(__('faq::faq.fields.updated'))->since()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                ContentFilters::shownIn(),
                ContentFilters::term('category', 'faq.category'),
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
