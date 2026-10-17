<?php

namespace Modules\Settings\Groups;

use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Platform\Settings\ModuleSettings;

/** FR-SET-03: social media links, any platform. */
class SocialSettings extends ModuleSettings
{
    public const PLATFORMS = ['facebook' => 'Facebook', 'youtube' => 'YouTube', 'linkedin' => 'LinkedIn', 'x' => 'X', 'twitter' => 'Twitter', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'whatsapp' => 'WhatsApp', 'pinterest' => 'Pinterest', 'email' => 'Email'];

    public function group(): string
    {
        return 'social';
    }

    public function label(): string
    {
        return __('settings::settings.groups.social');
    }

    public function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedShare;
    }

    public function sort(): int
    {
        return 30;
    }

    public function defaults(): array
    {
        return ['links' => []];
    }

    public function schema(): array
    {
        return [
            Repeater::make('links')->hiddenLabel()
                ->schema([
                    Select::make('platform')->label(__('settings::settings.fields.platform'))
                        ->options([...self::PLATFORMS, 'other' => __('settings::settings.fields.other')])
                        ->required()->live(),
                    TextInput::make('label')->label(__('settings::settings.fields.label'))
                        ->required(fn (Get $get): bool => $get('platform') === 'other')
                        ->visible(fn (Get $get): bool => $get('platform') === 'other')
                        ->maxLength(50),
                    // A web address, or mailto: for the Email platform
                    TextInput::make('url')->label(__('settings::settings.fields.url'))->required()->maxLength(500)
                        ->rule('regex:/^(https?:\/\/|mailto:)\S+$/i'),
                ])
                ->columns(3)
                ->reorderable()
                ->defaultItems(0)
                ->addActionLabel(__('settings::settings.actions.add_link'))
                ->columnSpanFull(),
        ];
    }
}
