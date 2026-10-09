<?php

namespace Modules\Page\Blocks;

use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Platform\Pages\PageBlock;

final class TextBlock extends PageBlock
{
    public static function name(): string
    {
        return 'text';
    }

    public static function label(): string
    {
        return __('page::page.blocks.text');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedBars3BottomLeft;
    }

    public static function schema(): array
    {
        return [PageBlocks::richText('body')->hiddenLabel()->required()];
    }
}
