<?php

namespace Modules\FacultyMember\Filament\Resources\FacultyMembers\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Department\Models\Department;
use Modules\FacultyMember\Models\FacultyMember;
use Platform\Taxonomy\Vocabularies;

/** Teachers in designation order (reorder inside it); search by name, designation note, employee ID, email. No create / delete: the faculty site owns them. */
class FacultyMembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['designation', 'department']))
            ->defaultSort(fn (Builder $query) => $query->directoryOrder())
            ->reorderable('sort_order', fn (): bool => (bool) auth()->user()?->can('reorder', FacultyMember::class))
            ->columns([
                ImageColumn::make('photo_url')->label('')->circular()->imageSize(36),
                TextColumn::make('name')->label(__('facultymember::facultymember.fields.name'))->wrap()
                    ->searchable(query: fn (Builder $query, string $search) => $query->searchText($search, ['name', 'designation_note', 'employee_id', 'email']))
                    ->description(fn (FacultyMember $record): ?string => $record->designationLabel()),
                TextColumn::make('department.short_name')->label(__('facultymember::facultymember.fields.department'))->badge()
                    ->placeholder(__('facultymember::facultymember.no_department')),
                TextColumn::make('employment_type')->label(__('facultymember::facultymember.fields.employment_type'))->badge()
                    ->formatStateUsing(fn (string $state): string => __("facultymember::facultymember.employment.{$state}"))
                    ->color(fn (string $state): string => $state === 'on_leave' ? 'warning' : 'gray')->toggleable(),
                TextColumn::make('employee_id')->label(__('facultymember::facultymember.fields.employee_id'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')->label(__('facultymember::facultymember.fields.email'))->toggleable(),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
                TextColumn::make('synced_at')->label(__('facultymember::facultymember.fields.synced_at'))->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('department_id')->label(__('facultymember::facultymember.fields.department'))
                    ->options(fn (): array => Department::query()->ordered()->get()->mapWithKeys(fn (Department $d): array => [$d->id => $d->name])->all())
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null) ? $query->inDepartments([(int) $data['value']]) : $query)
                    ->searchable(),
                SelectFilter::make('designation_term_id')->label(__('facultymember::facultymember.fields.designation'))
                    ->options(fn (): array => app(Vocabularies::class)->options(FacultyMember::DESIGNATION, activeOnly: false)),
                SelectFilter::make('employment_type')->label(__('facultymember::facultymember.fields.employment_type'))
                    ->options(collect(FacultyMember::EMPLOYMENT_TYPES)->mapWithKeys(fn (string $t): array => [$t => __("facultymember::facultymember.employment.{$t}")])->all()),
                TernaryFilter::make('no_department')->label(__('facultymember::facultymember.no_department'))
                    ->queries(true: fn (Builder $q) => $q->whereNull('department_id'), false: fn (Builder $q) => $q->whereNotNull('department_id')),
                TernaryFilter::make('is_active')->label(__('platform::relations.active')),
            ])
            ->recordActions([EditAction::make()->label(__('facultymember::facultymember.open'))]);
    }
}
