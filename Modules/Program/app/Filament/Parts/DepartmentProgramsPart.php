<?php

namespace Modules\Program\Filament\Parts;

use Modules\Program\Filament\Resources\Programs\ProgramResource;
use Modules\Program\Models\Program;
use Platform\Filament\Hub\RelatedRecordsPart;

/** "Programs" at the end of the Department editor: the Department's programs; "Add program" presets this Department. */
class DepartmentProgramsPart extends RelatedRecordsPart
{
    public static function icon(): ?string
    {
        return 'heroicon-o-academic-cap';
    }

    public static function sort(): int
    {
        return 20;
    }

    protected static function relationship(): string
    {
        return 'programs';
    }

    protected static function relatedResource(): string
    {
        return ProgramResource::class;
    }

    protected static function createKey(): ?string
    {
        return 'department';
    }

    protected static function columns(): array
    {
        return [
            __('program::program.fields.name') => fn (Program $p): string => "{$p->short_name} — {$p->name}",
            __('program::program.fields.level') => fn (Program $p): ?string => $p->level?->name,
            __('platform::hub.active') => fn (Program $p): string => $p->is_active ? '✓' : '—',
        ];
    }
}
