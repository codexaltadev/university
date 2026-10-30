<?php

namespace Modules\Office\Blocks;

use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Platform\Pages\PageBlock;

/**
 * "Honour Board" on a page of an office's site (DIU /registrar-office/honour-board): the head now and
 * every former head with their terms — from the office's Head assignments, not a table typed in.
 */
final class OfficeHeadsBlock extends PageBlock
{
    public static function name(): string
    {
        return 'office-heads';
    }

    public static function label(): string
    {
        return __('office::office.blocks.heads');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedTrophy;
    }

    public static function view(): string
    {
        return 'office::blocks.heads';
    }

    public static function schema(): array
    {
        return [
            Textarea::make('intro')->label(__('office::office.blocks.intro'))->rows(2)->maxLength(500)
                ->helperText(__('office::office.blocks.heads_help')),
        ];
    }
}
