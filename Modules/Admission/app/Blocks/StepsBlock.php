<?php

namespace Modules\Admission\Blocks;

use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Platform\Pages\PageBlock;

/** How to apply: the steps in order (FR-ADM-01). */
final class StepsBlock extends PageBlock
{
    public static function name(): string
    {
        return 'admission-steps';
    }

    public static function label(): string
    {
        return __('admission::admission.blocks.steps');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedListBullet;
    }

    public static function view(): string
    {
        return 'admission::blocks.steps';
    }

    public static function schema(): array
    {
        return [

        ];
    }
}
