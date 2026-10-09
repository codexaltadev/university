<?php

namespace Modules\Page\Blocks;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Platform\Filament\Components\MediaPicker;
use Platform\Pages\PageBlock;

final class FilesBlock extends PageBlock
{
    public static function name(): string
    {
        return 'files';
    }

    public static function label(): string
    {
        return __('page::page.blocks.files');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedPaperClip;
    }

    public static function schema(): array
    {
        return [
            TextInput::make('heading')->label(__('page::page.fields.heading'))->maxLength(255),
            MediaPicker::make('files')->label(__('page::page.blocks.files'))->multiple()->storeIds()->required(),
        ];
    }

    public static function mediaIds(array $data): array
    {
        return array_map('intval', (array) ($data['files'] ?? []));
    }
}
