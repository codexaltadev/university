<?php

namespace Modules\Program\Filament\Resources\Programs\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Program\Models\Program;
use Platform\Taxonomy\Vocabularies;

class ProgramsTable
{
    /** $within: inside a Hub ("department" / "faculty") — the columns and filters it fixes are left out. */
    public static function configure(Table $table, ?string $within = null): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['department.faculty', 'level']))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns(array_filter([
                TextColumn::make('short_name')->label(__('program::program.fields.short_name'))->badge()->searchable()->sortable(),
                TextColumn::make('name')->label(__('program::program.fields.name'))->searchable()
                    ->description(fn (Program $record): ?string => $record->url()),
                $within === 'department' ? null : TextColumn::make('department.short_name')->label(__('program::program.fields.department'))
                    ->description(fn (Program $record): ?string => $record->department?->faculty?->short_name),
                TextColumn::make('level.name')->label(__('program::program.fields.level')),
                TextColumn::make('shift')->label(__('program::program.fields.shift'))
                    ->formatStateUsing(fn (?string $state): ?string => $state ? __("program::program.shifts.{$state}") : null)->toggleable(),
                IconColumn::make('show_in_listing')->label(__('program::program.fields.show_in_listing'))->boolean()->toggleable(),
                ToggleColumn::make('is_active')->label(__('platform::relations.active'))
                    ->disabled(fn ($record): bool => ! auth()->user()?->can('update', $record)),   // only who may update (a viewer in a Hub may not)
                TextColumn::make('published_at')->label(__('platform::relations.published_at'))->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ]))
            ->filters(array_filter([
                $within !== null ? null : Filter::make('faculty')->schema([
                    Select::make('faculty_id')->label(__('program::program.fields.faculty'))
                        ->options(fn (): array => Faculty::query()->ordered()->pluck('short_name', 'id')->all()),
                ])->query(fn ($query, array $data) => $query->when($data['faculty_id'] ?? null, fn ($q, $id) => $q->whereHas('department', fn ($d) => $d->where('faculty_id', $id)))),
                $within === 'department' ? null : SelectFilter::make('department_id')->label(__('program::program.fields.department'))
                    ->options(fn (): array => Department::query()->ordered()->pluck('short_name', 'id')->all()),
                SelectFilter::make('level_term_id')->label(__('program::program.fields.level'))
                    ->options(fn (): array => app(Vocabularies::class)->options('program.level', activeOnly: false)),
                SelectFilter::make('shift')->label(__('program::program.fields.shift'))
                    ->options(collect(Program::SHIFTS)->mapWithKeys(fn (string $s): array => [$s => __("program::program.shifts.{$s}")])->all()),
                TernaryFilter::make('is_active')->label(__('platform::relations.active')),
                TrashedFilter::make(),
            ]))
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }
}
