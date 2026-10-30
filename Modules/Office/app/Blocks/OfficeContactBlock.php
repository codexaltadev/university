<?php

namespace Modules\Office\Blocks;

use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Platform\Pages\PageBlock;

/**
 * "Contact" on a page of an office's site (DIU /registrar-office/contact): the office's location, phone,
 * email and hours as cards, its campus with the map, and who to contact for what — all from the office.
 */
final class OfficeContactBlock extends PageBlock
{
    public static function name(): string
    {
        return 'office-contact';
    }

    public static function label(): string
    {
        return __('office::office.blocks.contact');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedMapPin;
    }

    public static function view(): string
    {
        return 'office::blocks.contact';
    }

    public static function schema(): array
    {
        return [
            Textarea::make('intro')->label(__('office::office.blocks.intro'))->rows(2)->maxLength(500)
                ->helperText(__('office::office.blocks.contact_help')),
        ];
    }
}
