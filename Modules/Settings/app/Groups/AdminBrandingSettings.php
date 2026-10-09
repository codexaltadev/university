<?php

namespace Modules\Settings\Groups;

use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Platform\Filament\Forms\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * FR-SET-13, UI/UX Part A §11: panel name, colour, font and login message over a
 * neutral default; layout and navigation never change. Logos, favicon and login
 * background come with the Media Library (Phase 2.5); "copy from website
 * branding" with Theme Management.
 */
class AdminBrandingSettings extends ModuleSettings
{
    public const NEUTRAL_NAME = 'University Admin';

    public const NEUTRAL_COLOR = '#475569';   // slate-600, neutral blue-gray

    public const FONTS = ['Inter', 'Roboto', 'Open Sans', 'Lato', 'Poppins', 'Montserrat', 'Manrope', 'Hind Siliguri', 'Noto Sans Bengali'];

    public function group(): string
    {
        return 'admin_branding';
    }

    public function label(): string
    {
        return __('settings::settings.groups.admin_branding');
    }

    public function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedSwatch;
    }

    public function sort(): int
    {
        return 90;
    }

    public function translatable(): array
    {
        return ['panel_name', 'login_message'];
    }

    public function defaults(): array
    {
        return ['primary_color' => self::NEUTRAL_COLOR, 'font' => 'Inter'];
    }

    public function schema(): array
    {
        return [
            TranslatableTabs::make(fn (string $locale): array => [
                TextInput::make("panel_name.{$locale}")->label(__('settings::settings.fields.panel_name'))
                    ->placeholder(self::NEUTRAL_NAME)->maxLength(60),
                Textarea::make("login_message.{$locale}")->label(__('settings::settings.fields.login_message'))->rows(2)->maxLength(500),
            ], 'branding-texts'),
            ColorPicker::make('primary_color')->label(__('settings::settings.fields.primary_color'))
                ->hex()->regex('/^#[0-9a-fA-F]{6}$/')->required()->live()
                ->hint(fn (?string $state): ?string => $state && ! static::readableOnWhite($state) ? __('settings::settings.help.low_contrast') : null)
                ->hintColor('warning'),
            Select::make('font')->label(__('settings::settings.fields.font'))
                ->options(array_combine(self::FONTS, self::FONTS))->required()->selectablePlaceholder(false),
        ];
    }

    /** Buttons are white text on the primary colour (WCAG AA for large text / UI: 3:1). */
    public static function readableOnWhite(string $hex): bool
    {
        return Color::calculateContrastRatio($hex, '#ffffff') >= 3;
    }
}
