<?php

namespace Modules\Program\Filament\Parts;

use Modules\Program\Filament\Resources\Programs\ProgramResource;
use Modules\Program\Models\Program;
use Platform\Filament\Hub\RelatedRecordsPart;

/** "Programs" at the end of the Faculty editor: every program of its departments; new ones are added from a Department. */
class FacultyProgramsPart extends RelatedRecordsPart
{
    public static function icon(): ?string
    {
        return 'heroicon-o-academic-cap';
    }

    public static function sort(): int
    {
        return 30;
    }

    protected static function relationship(): string
    {
        return 'programs';
    }

    protected static function relatedResource(): string
    {
        return ProgramResource::class;
    }

    protected static function columns(): array
    {
        return [
            __('program::program.fields.name') => fn (Program $p): string => "{$p->short_name} — {$p->name}",
            __('program::program.fields.department') => fn (Program $p): ?string => $p->department?->short_name,
            __('platform::hub.active') => fn (Program $p): string => $p->is_active ? '✓' : '—',
        ];
    }
}
