<?php

namespace Modules\Page\Blocks;

use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Platform\Pages\PageBlock;

final class ButtonBlock extends PageBlock
{
    public static function name(): string
    {
        return 'button';
    }

    public static function label(): string
    {
        return __('page::page.blocks.button');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedCursorArrowRays;
    }

    public static function schema(): array
    {
        return [
            TextInput::make('label')->label(__('page::page.fields.label'))->required()->maxLength(80),
            TextInput::make('url')->label(__('page::page.fields.url'))->required()->maxLength(1000)
                ->regex('#^(https?://|/)#')->helperText(__('page::page.help.button_url')),
            Select::make('style')->label(__('page::page.fields.style'))
                ->options(['primary' => __('page::page.styles.primary'), 'secondary' => __('page::page.styles.secondary')])
                ->default('primary')->selectablePlaceholder(false),
        ];
    }
}
