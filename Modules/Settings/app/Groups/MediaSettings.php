<?php

namespace Modules\Settings\Groups;

use BackedEnum;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Platform\Settings\ModuleSettings;

/**
 * FR-SET-06: upload rules. Default images (FR-SET-04) are added with the Media
 * Library (Phase 2.5), because they are picked from it.
 */
class MediaSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'media';
    }

    public function label(): string
    {
        return __('settings::settings.groups.media');
    }

    public function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedPhoto;
    }

    public function sort(): int
    {
        return 40;
    }

    public function defaults(): array
    {
        return [
            'allowed_types' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip'],
            'max_upload_mb' => 10,
            'image_quality' => 80,
        ];
    }

    public function schema(): array
    {
        return [
            TagsInput::make('allowed_types')->label(__('settings::settings.fields.allowed_types'))
                ->helperText(__('settings::settings.help.allowed_types'))->required()->columnSpanFull(),
            TextInput::make('max_upload_mb')->label(__('settings::settings.fields.max_upload_mb'))
                ->numeric()->integer()->minValue(1)->maxValue(200)->suffix('MB')->required(),
            TextInput::make('image_quality')->label(__('settings::settings.fields.image_quality'))
                ->numeric()->integer()->minValue(40)->maxValue(100)->suffix('%')->required(),
        ];
    }
}
