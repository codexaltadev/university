<?php

namespace Modules\International\Settings;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › International (FR-INTL-03): an expired agreement is hidden or marked
 * "Expired"; how many days before its end the admins are told.
 * Read: settings('international.expired'), settings('international.notify_days').
 */
class InternationalSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'international';
    }

    public function label(): string
    {
        return __('international::international.title');
    }

    public function defaults(): array
    {
        return [
            'expired' => 'hide',
            'notify_days' => 60,
            'visitors_title' => ['en' => 'Distinguished Visitors', 'bn' => 'বিশিষ্ট অতিথি'],
            'visitors_text' => ['en' => 'Guests from Bangladesh and across the globe on their visit to Daffodil International University', 'bn' => 'দেশ-বিদেশের অতিথিরা ড্যাফোডিল ইন্টারন্যাশনাল ইউনিভার্সিটি ঘুরে যা বলেছেন'],
            'visitors_image' => null,
        ];
    }

    public function translatable(): array
    {
        return ['visitors_title', 'visitors_text'];
    }

    public function media(): array
    {
        return ['visitors_image'];
    }

    public function schema(): array
    {
        return [
            Radio::make('expired')->label(__('international::international.settings.expired'))->inline()
                ->options(['hide' => __('international::international.settings.hide'), 'label' => __('international::international.settings.label')]),
            TextInput::make('notify_days')->label(__('international::international.settings.notify_days'))->integer()->minValue(1)->maxValue(365)->dehydrateStateUsing(fn ($state): int => (int) $state),
            Section::make(__('international::international.settings.visitors'))->description(__('international::international.settings.visitors_help'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("visitors_title.{$locale}")->label(__('international::international.settings.title'))->maxLength(150),
                    Textarea::make("visitors_text.{$locale}")->label(__('international::international.settings.text'))->rows(2)->maxLength(500),
                ]),
                MediaPicker::make('visitors_image')->label(__('international::international.settings.image'))->image()->storeIds(),
            ]),
        ];
    }
}
