<?php

namespace Modules\Tuition\Filament\Parts;

use Modules\Tuition\Filament\Resources\Tuitions\TuitionResource;
use Modules\Tuition\Models\Tuition;
use Platform\Filament\Hub\RelatedRecordsPart;

/**
 * "Tuition Fees" at the end of the Program editor (UI/UX Part A §5.2.1): the program's
 * tuitions, read only — the tuition team edits them in Academic › Tuition Fees.
 */
class ProgramTuitionPart extends RelatedRecordsPart
{
    public static function label(): string
    {
        return __('tuition::tuition.tuition_fees');
    }

    public static function icon(): ?string
    {
        return 'heroicon-o-banknotes';
    }

    protected static function relationship(): string
    {
        return 'tuitions';
    }

    protected static function relatedResource(): string
    {
        return TuitionResource::class;
    }

    protected static function createKey(): ?string
    {
        return 'program';
    }

    protected static function columns(): array
    {
        return [
            __('tuition::tuition.fields.category') => fn (Tuition $t): string => trim($t->category?->name.(filled($t->label) ? ' · '.$t->label : '')),
            __('tuition::tuition.fields.credits') => fn (Tuition $t): ?string => $t->creditsText(),
            __('tuition::tuition.fields.tuition_total') => fn (Tuition $t): ?string => static::range($t, 'tuition'),
            __('tuition::tuition.fields.total') => fn (Tuition $t): ?string => static::range($t, 'total'),
            __('tuition::tuition.fields.active') => fn (Tuition $t): string => $t->is_active ? '✓' : '—',
        ];
    }

    protected static function range(Tuition $tuition, string $field): ?string
    {
        $min = $tuition->{"{$field}_min"};
        if ($min === null) {
            return null;
        }
        $max = $tuition->{"{$field}_max"};

        return $tuition->category?->money($min).($max !== null && (float) $max !== (float) $min ? ' – '.$tuition->category?->money($max) : '');
    }
}
