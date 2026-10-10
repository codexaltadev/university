<?php

namespace Modules\Department\Filament\Parts;

use Modules\Department\Filament\Resources\Departments\DepartmentResource;
use Modules\Department\Models\Department;
use Platform\Filament\Hub\RelatedRecordsPart;

/** "Departments" at the end of the Faculty editor: the Faculty's departments; "Add department" presets this Faculty. */
class FacultyDepartmentsPart extends RelatedRecordsPart
{
    public static function icon(): ?string
    {
        return 'heroicon-o-building-office-2';
    }

    public static function sort(): int
    {
        return 20;
    }

    protected static function relationship(): string
    {
        return 'departments';
    }

    protected static function relatedResource(): string
    {
        return DepartmentResource::class;
    }

    protected static function createKey(): ?string
    {
        return 'faculty';
    }

    protected static function columns(): array
    {
        return [
            __('department::department.fields.name') => fn (Department $d): string => "{$d->short_name} — {$d->name}",
            __('platform::hub.active') => fn (Department $d): string => $d->is_active ? '✓' : '—',
        ];
    }
}
