<?php

namespace Modules\Page\Blocks;

use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;
use Platform\Pages\PageBlock;

/** Link cards (DIU's /students, /teachers; boxes: /career's values): a heading over a grid of cards, each an icon, a label, a link and a line of text. */
final class LinksBlock extends PageBlock
{
    public static function name(): string
    {
        return 'links';
    }

    public static function label(): string
    {
        return __('page::page.blocks.links');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedSquares2x2;
    }

    public static function schema(): array
    {
        return [
            TextInput::make('heading')->label(__('page::page.fields.heading'))->maxLength(150),
            Select::make('columns')->label(__('page::page.fields.columns'))->options([2 => '2', 3 => '3', 4 => '4'])->default(3)->selectablePlaceholder(false),
            // tiles: DIU's /students cards; boxes: DIU's /career "DIU Values" — bordered, a line of text, the link optional
            Select::make('style')->label(__('page::page.fields.style'))
                ->options(['tiles' => __('page::page.styles.tiles'), 'boxes' => __('page::page.styles.boxes')])->default('tiles')->selectablePlaceholder(false),
            Repeater::make('items')->label(__('page::page.fields.links'))
                ->schema([
                    TextInput::make('label')->label(__('page::page.fields.label'))->required()->maxLength(100),
                    TextInput::make('url')->label(__('page::page.fields.url'))->maxLength(1000)
                        ->regex('#^(https?://|/)#')->helperText(__('page::page.help.button_url')),
                    Textarea::make('text')->label(__('page::page.fields.body'))->rows(2)->maxLength(400)->columnSpanFull(),
                    TextInput::make('icon')->label(__('page::page.fields.icon'))->maxLength(40)->helperText(__('page::page.help.icon')),
                    Toggle::make('new_tab')->label(__('page::page.fields.new_tab')),
                ])
                ->columns(2)
                ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                ->collapsible()->reorderable()->minItems(1),
        ];
    }
}
