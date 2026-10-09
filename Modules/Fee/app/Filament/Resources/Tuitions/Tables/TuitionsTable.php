<?php

namespace Modules\Fee\Filament\Resources\Tuitions\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Modules\Faculty\Models\Faculty;
use Modules\Fee\Models\FeeCategory;
use Modules\Fee\Models\Tuition;

class TuitionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['program.department', 'category']))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('program.name')->label(__('fee::fee.fields.program'))->searchable()
                    ->description(fn (Tuition $record): ?string => trim(($record->program?->department?->short_name ?? '').(filled($record->label) ? ' · '.$record->label : ''), ' ·') ?: null),
                TextColumn::make('category.name')->label(__('fee::fee.fields.category'))->badge(),
                TextColumn::make('credit_min')->label(__('fee::fee.fields.credits'))->formatStateUsing(fn ($state, Tuition $record): ?string => $record->creditsText()),
                TextColumn::make('total_min')->label(__('fee::fee.fields.total'))
                    ->formatStateUsing(fn ($state, Tuition $record): ?string => $record->category?->money($record->total_min).($record->hasRange() ? ' – '.$record->category?->money($record->total_max) : ''))
                    ->description(fn (Tuition $record): ?string => $record->totals_overridden ? __('fee::fee.fields.totals_overridden') : null),
                ToggleColumn::make('is_active')->label(__('fee::fee.fields.active')),
            ])
            ->filters([
                SelectFilter::make('fee_category_id')->label(__('fee::fee.fields.category'))
                    ->options(fn (): array => FeeCategory::query()->ordered()->get()->mapWithKeys(fn (FeeCategory $c): array => [$c->id => $c->name])->all()),
                Filter::make('faculty')->schema([
                    Select::make('faculty_id')->label(__('fee::fee.fields.faculty'))->options(fn (): array => Faculty::query()->ordered()->pluck('short_name', 'id')->all()),
                ])->query(fn ($query, array $data) => $query->when($data['faculty_id'] ?? null, fn ($q, $id) => $q->whereHas('program.department', fn ($d) => $d->where('faculty_id', $id)))),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }
}
