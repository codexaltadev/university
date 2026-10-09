<?php

namespace Modules\Taxonomy\Filament\Resources\Vocabularies\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Taxonomy\Filament\Resources\Vocabularies\Schemas\VocabularyForm;
use Platform\Taxonomy\Vocabulary;

class VocabulariesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('terms'))
            ->defaultSort('key')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label(__('taxonomy::taxonomy.fields.name'))
                    ->description(fn (Vocabulary $record): string => VocabularyForm::structure($record))
                    ->weight('medium'),
                TextColumn::make('module_slug')->label(__('taxonomy::taxonomy.fields.module'))
                    ->badge()->color('gray')
                    ->placeholder(__('taxonomy::taxonomy.shared')),
                TextColumn::make('terms_count')->label(__('taxonomy::taxonomy.fields.terms'))->numeric(),
            ])
            ->recordActions([
                EditAction::make()->label(__('taxonomy::taxonomy.actions.manage')),
            ]);
    }
}
