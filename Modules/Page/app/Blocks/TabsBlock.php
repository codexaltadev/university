<?php

namespace Modules\Page\Blocks;

use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Platform\Pages\PageBlock;

final class TabsBlock extends PageBlock
{
    public static function name(): string
    {
        return 'tabs';
    }

    public static function label(): string
    {
        return __('page::page.blocks.tabs');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedRectangleGroup;
    }

    public static function schema(): array
    {
        return [
            Repeater::make('tabs')->hiddenLabel()
                ->schema([
                    TextInput::make('label')->label(__('page::page.fields.tab'))->required()->maxLength(80),
                    PageBlocks::richText('body')->label(__('page::page.fields.body'))->required(),
                ])
                ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                ->collapsible()->reorderable()->minItems(2),
        ];
    }
}
