<?php

namespace Modules\Page\Blocks;

use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Platform\Pages\PageBlock;

final class AccordionBlock extends PageBlock
{
    public static function name(): string
    {
        return 'accordion';
    }

    public static function label(): string
    {
        return __('page::page.blocks.accordion');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedQueueList;
    }

    public static function schema(): array
    {
        return [
            Repeater::make('items')->hiddenLabel()
                ->schema([
                    TextInput::make('title')->label(__('page::page.fields.heading'))->required()->maxLength(255),
                    PageBlocks::richText('body')->label(__('page::page.fields.body'))->required(),
                ])
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                ->collapsible()->reorderable()->minItems(1),
        ];
    }
}
