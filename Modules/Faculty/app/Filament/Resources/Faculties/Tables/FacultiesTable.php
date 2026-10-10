<?php

namespace Modules\Faculty\Filament\Resources\Faculties\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Modules\Faculty\Models\Faculty;

class FacultiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('short_name')->label(__('faculty::faculty.fields.short_name'))->badge()->searchable()->sortable(),
                TextColumn::make('name')->label(__('faculty::faculty.fields.name'))->searchable()
                    ->description(fn (Faculty $record): ?string => $record->url()),
                IconColumn::make('show_in_listing')->label(__('faculty::faculty.fields.show_in_listing'))->boolean(),
                ToggleColumn::make('is_active')->label(__('platform::relations.active'))
                    ->disabled(fn ($record): bool => ! auth()->user()?->can('update', $record)),   // only who may update (a viewer in a Hub may not)
                TextColumn::make('updated_at')->label(__('faculty::faculty.fields.updated_at'))->since()->sortable()->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label(__('platform::relations.active')),
                TernaryFilter::make('show_in_listing')->label(__('faculty::faculty.fields.show_in_listing')),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }
}
