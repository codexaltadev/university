<?php

namespace Modules\Program\Blocks;

use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Modules\Faculty\Models\Faculty;
use Platform\Filament\Components\LinkListField;
use Platform\Pages\PageBlock;

/**
 * Admission eligibility of one faculty (DIU's /admission/faculty/{slug}): the faculty's name and overview, its listed
 * programs by level (Undergraduate / Postgraduate) with each program's eligibility for general and English-medium
 * students, and the links shown under it (Tuition Fees, Waiver & Scholarship, Online Admission …).
 */
final class EligibilityBlock extends PageBlock
{
    public static function name(): string
    {
        return 'program-eligibility';
    }

    public static function label(): string
    {
        return __('program::program.eligibility_block.label');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedAcademicCap;
    }

    public static function view(): string
    {
        return 'program::blocks.eligibility';
    }

    public static function schema(): array
    {
        return [
            Select::make('faculty')->label(__('program::program.eligibility_block.faculty'))->required()->searchable()
                ->options(fn (): array => Faculty::query()->ordered()->get()->mapWithKeys(fn (Faculty $f): array => [$f->id => (string) $f->name])->all()),
            LinkListField::make('links')->label(__('program::program.eligibility_block.links'))
                ->helperText(__('program::program.eligibility_block.links_help')),
        ];
    }
}
