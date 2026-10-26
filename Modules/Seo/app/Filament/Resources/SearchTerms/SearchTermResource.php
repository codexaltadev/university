<?php

namespace Modules\Seo\Filament\Resources\SearchTerms;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Seo\Filament\Resources\SearchTerms\Pages\ListSearchTerms;
use Modules\Seo\Models\SearchTerm;
use UnitEnum;

/**
 * Site › Search terms (Phase 4.7g): what visitors search on the website, most searched first; "No results" shows what
 * they do not find — content to write, or a synonym to add in Settings › Search.
 */
class SearchTermResource extends Resource
{
    protected static ?string $model = SearchTerm::class;

    protected static string|UnitEnum|null $navigationGroup = 'Site';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?int $navigationSort = 72;

    protected static ?string $recordTitleAttribute = 'term';

    public static function getModelLabel(): string
    {
        return __('seo::seo.search.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('seo::seo.search.plural');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('hits', 'desc')
            ->columns([
                TextColumn::make('term')->label(__('seo::seo.search.term'))->searchable()->limit(70)->copyable(),
                TextColumn::make('hits')->label(__('seo::seo.fields.hits'))->numeric()->sortable(),
                TextColumn::make('last_results')->label(__('seo::seo.search.results'))->numeric()->sortable()
                    ->badge()->color(fn (int $state): string => $state === 0 ? 'danger' : 'success'),
                TextColumn::make('last_searched_at')->label(__('seo::seo.search.last_searched_at'))->since()->sortable(),
                TextColumn::make('first_searched_at')->label(__('seo::seo.fields.first_seen_at'))->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('found')->label(__('seo::seo.search.found'))
                    ->queries(true: fn (Builder $q) => $q->where('last_results', '>', 0), false: fn (Builder $q) => $q->where('last_results', 0)),
            ])
            ->recordActions([
                Action::make('open')->label(__('seo::seo.search.open'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (SearchTerm $record): string => route('search', ['q' => $record->term]), shouldOpenInNewTab: true),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ListSearchTerms::route('/')];
    }
}
