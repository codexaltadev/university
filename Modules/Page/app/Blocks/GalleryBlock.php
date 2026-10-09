<?php

namespace Modules\Page\Blocks;

use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Platform\Filament\Components\MediaPicker;
use Platform\Pages\PageBlock;

final class GalleryBlock extends PageBlock
{
    public static function name(): string
    {
        return 'gallery';
    }

    public static function label(): string
    {
        return __('page::page.blocks.gallery');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedSquares2x2;
    }

    public static function schema(): array
    {
        return [
            MediaPicker::make('images')->label(__('page::page.blocks.gallery'))->image()->multiple()->storeIds()->required(),
            Select::make('columns')->label(__('page::page.fields.columns'))->options([2 => 2, 3 => 3, 4 => 4])->default(3)->selectablePlaceholder(false),
        ];
    }

    public static function mediaIds(array $data): array
    {
        return array_map('intval', (array) ($data['images'] ?? []));
    }
}
