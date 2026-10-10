<?php

namespace Modules\Program\Filament\Resources\Tuitions\Tables;

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
use Modules\Program\Models\FeeCategory;
use Modules\Program\Models\Tuition;

class TuitionsTable
{
    /** $inProgram: inside a Program Hub — no program column, no faculty filter, no reordering. */
    public static function configure(Table $table, bool $inProgram = false): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['program.department', 'category']))
            ->defaultSort('sort_order')
            ->reorderable('sort_order', ! $inProgram)
            ->columns(array_filter([
                $inProgram ? null : TextColumn::make('program.name')->label(__('program::tuition.fields.program'))->searchable()
                    ->description(fn (Tuition $record): ?string => trim(($record->program?->department?->short_name ?? '').(filled($record->label) ? ' · '.$record->label : ''), ' ·') ?: null),
                TextColumn::make('category.name')->label(__('program::tuition.fields.category'))->badge(),
                TextColumn::make('credit_min')->label(__('program::tuition.fields.credits'))->formatStateUsing(fn ($state, Tuition $record): ?string => $record->creditsText()),
                TextColumn::make('total_min')->label(__('program::tuition.fields.total'))
                    ->formatStateUsing(fn ($state, Tuition $record): ?string => $record->category?->money($record->total_min).($record->hasRange() ? ' – '.$record->category?->money($record->total_max) : ''))
                    ->description(fn (Tuition $record): ?string => $record->totals_overridden ? __('program::tuition.fields.totals_overridden') : null),
                ToggleColumn::make('is_active')->label(__('program::tuition.fields.active'))
                    ->disabled(fn ($record): bool => ! auth()->user()?->can('update', $record)),   // only who may update (a viewer in a Hub may not)
            ]))
            ->filters(array_filter([
                SelectFilter::make('fee_category_id')->label(__('program::tuition.fields.category'))
                    ->options(fn (): array => FeeCategory::query()->ordered()->get()->mapWithKeys(fn (FeeCategory $c): array => [$c->id => $c->name])->all()),
                $inProgram ? null : Filter::make('faculty')->schema([
                    Select::make('faculty_id')->label(__('program::tuition.fields.faculty'))->options(fn (): array => Faculty::query()->ordered()->pluck('short_name', 'id')->all()),
                ])->query(fn ($query, array $data) => $query->when($data['faculty_id'] ?? null, fn ($q, $id) => $q->whereHas('program.department', fn ($d) => $d->where('faculty_id', $id)))),
                TrashedFilter::make(),
            ]))
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }
}
