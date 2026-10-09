<?php

namespace Modules\Page\Blocks;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Platform\Pages\PageBlock;

final class EmbedBlock extends PageBlock
{
    public static function name(): string
    {
        return 'embed';
    }

    public static function label(): string
    {
        return __('page::page.blocks.embed');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedPlayCircle;
    }

    /** YouTube, Vimeo, Google Maps, Facebook video: the theme turns the link into the player. */
    public static function schema(): array
    {
        return [
            TextInput::make('url')->label(__('page::page.fields.url'))->url()->required()->maxLength(1000)
                ->regex('#^https://(www\.)?(youtube\.com|youtu\.be|vimeo\.com|player\.vimeo\.com|google\.com/maps|maps\.google\.com|facebook\.com)/#i')
                ->helperText(__('page::page.help.embed')),
            TextInput::make('caption')->label(__('page::page.fields.caption'))->maxLength(255),
        ];
    }
}
