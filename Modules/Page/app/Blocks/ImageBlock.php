<?php

namespace Modules\Page\Blocks;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Platform\Filament\Components\MediaPicker;
use Platform\Pages\PageBlock;

final class ImageBlock extends PageBlock
{
    public static function name(): string
    {
        return 'image';
    }

    public static function label(): string
    {
        return __('page::page.blocks.image');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedPhoto;
    }

    public static function schema(): array
    {
        return [
            MediaPicker::make('image')->label(__('page::page.blocks.image'))->image()->storeIds()->required(),
            TextInput::make('caption')->label(__('page::page.fields.caption'))->maxLength(255),
        ];
    }

    public static function mediaIds(array $data): array
    {
        return array_filter([(int) ($data['image'] ?? 0)]);
    }
}
