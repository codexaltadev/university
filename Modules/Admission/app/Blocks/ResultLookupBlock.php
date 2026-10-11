<?php

namespace Modules\Admission\Blocks;

use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Platform\Pages\PageBlock;

/** The admission result lookup on any page (FR-ADM-06). */
final class ResultLookupBlock extends PageBlock
{
    public static function name(): string
    {
        return 'admission-result-lookup';
    }

    public static function label(): string
    {
        return __('admission::admission.blocks.result-lookup');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedClipboardDocumentCheck;
    }

    public static function view(): string
    {
        return 'admission::blocks.result-lookup';
    }

    public static function schema(): array
    {
        return [

        ];
    }
}
