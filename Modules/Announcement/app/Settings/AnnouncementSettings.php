<?php

namespace Modules\Announcement\Settings;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Announcements (owner 2026-10-25): each kind — popup, ticker, bar, countdown —
 * shows on the website only once it is turned on here (all off at first); the ticker's label and
 * the popup's second button. Read: settings('announcement.popup') …, settings('announcement.ticker_label').
 */
class AnnouncementSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'announcement';
    }

    public function label(): string
    {
        return __('announcement::announcement.plural');
    }

    public function defaults(): array
    {
        return [
            'popup' => false,
            'ticker' => false,
            'bar' => false,
            'countdown' => false,
            'ticker_label' => ['en' => 'Latest', 'bn' => 'সর্বশেষ'],
            'popup_later' => ['en' => 'Maybe Later', 'bn' => 'পরে দেখব'],
        ];
    }

    public function translatable(): array
    {
        return ['ticker_label', 'popup_later'];
    }

    public function schema(): array
    {
        return [
            Section::make(__('announcement::announcement.settings.shown'))->description(__('announcement::announcement.settings.shown_help'))->compact()->columns(2)->schema(
                array_map(fn (string $type): Toggle => Toggle::make($type)->label(__("announcement::announcement.types.{$type}"))
                    ->helperText(__("announcement::announcement.type_help.{$type}")), ['popup', 'ticker', 'bar', 'countdown']),
            ),
            Section::make(__('announcement::announcement.settings.texts'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("ticker_label.{$locale}")->label(__('announcement::announcement.settings.ticker_label'))->maxLength(40),
                    TextInput::make("popup_later.{$locale}")->label(__('announcement::announcement.settings.popup_later'))->maxLength(40),
                ]),
            ]),
        ];
    }
}
