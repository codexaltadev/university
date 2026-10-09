<?php

namespace Modules\Department\Filament\Resources\Departments\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;

class DepartmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('faculty'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('short_name')->label(__('department::department.fields.short_name'))->badge()->searchable()->sortable(),
                TextColumn::make('name')->label(__('department::department.fields.name'))->searchable()
                    ->description(fn (Department $record): ?string => $record->listingUrl()),
                TextColumn::make('faculty.short_name')->label(__('department::department.fields.faculty'))->sortable(),
                IconColumn::make('show_in_listing')->label(__('department::department.fields.show_in_listing'))->boolean(),
                ToggleColumn::make('is_active')->label(__('platform::relations.active')),
                TextColumn::make('updated_at')->label(__('department::department.fields.updated_at'))->since()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('faculty_id')->label(__('department::department.fields.faculty'))
                    ->options(fn (): array => Faculty::query()->ordered()->pluck('short_name', 'id')->all()),
                TernaryFilter::make('is_active')->label(__('platform::relations.active')),
                TernaryFilter::make('show_in_listing')->label(__('department::department.fields.show_in_listing')),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }
}
