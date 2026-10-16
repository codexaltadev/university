<?php

namespace Modules\Administration\Filament\Resources\Members\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Administration\Filament\Resources\Categories\AdministrationCategoryResource;
use Modules\Administration\Models\AdministrationCategory;
use Modules\Administration\Models\Member;

/** Members in directory order (category ladder, then order); search by name, designation, employee ID, email, phone. */
class MembersTable
{
    protected const OFFICE = 'Modules\\Office\\Models\\Office';

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('category'))
            ->defaultSort(fn (Builder $query) => $query->directoryOrder())
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('photo')->label('')->circular()->imageSize(36)
                    ->state(fn (Member $record): ?string => $record->firstMedia('photo')?->url('thumb')),
                TextColumn::make('name')->label(__('administration::administration.fields.name'))->wrap()
                    ->searchable(query: fn (Builder $query, string $search) => $query->searchText($search, ['name', 'designation', 'employee_id', 'email', 'phone']))
                    ->description(fn (Member $record): ?string => $record->designation ?: null),
                TextColumn::make('category.name')->label(__('administration::administration.category.singular'))->badge()
                    ->color(fn (Member $record): string => $record->category?->is_public ? 'primary' : 'gray'),
                TextColumn::make('employee_id')->label(__('administration::administration.fields.employee_id'))->toggleable(),
                TextColumn::make('email')->label(__('administration::administration.fields.email'))->toggleable(),
                TextColumn::make('phone')->label(__('administration::administration.fields.phone'))
                    ->description(fn (Member $record): ?string => $record->extension ? 'Ext. '.$record->extension : null)->toggleable(),
                ToggleColumn::make('is_active')->label(__('platform::relations.active'))
                    ->disabled(fn ($record): bool => ! auth()->user()?->can('update', $record)),
            ])
            ->filters([
                SelectFilter::make('group')->label(__('administration::administration.fields.group'))
                    ->options(AdministrationCategoryResource::groups())
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null) ? $query->whereHas('category', fn ($c) => $c->where('group', $data['value'])) : $query),
                SelectFilter::make('category_id')->label(__('administration::administration.category.singular'))
                    ->options(fn (): array => AdministrationCategory::query()->ordered()->get()->mapWithKeys(fn ($c): array => [$c->id => $c->name])->all()),
                SelectFilter::make('primary_office_id')->label(__('administration::administration.fields.primary_office'))
                    ->options(fn (): array => class_exists(self::OFFICE) ? (self::OFFICE)::query()->ordered()->get()->mapWithKeys(fn ($o): array => [$o->id => $o->name])->all() : [])
                    ->visible(fn (): bool => class_exists(self::OFFICE)),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }
}
