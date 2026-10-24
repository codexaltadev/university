<?php

namespace Modules\Lab\Settings;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Labs: the lab pages' own text — the /labs title, text and picture on top, and
 * a department's labs page's lines: in the hero (:department = its name) and under its name.
 * DIU's are the defaults.
 * Read: settings('lab.listing_title'), media_url(settings('lab.listing_image')), settings('lab.department_hero'), settings('lab.department_text').
 */
class LabSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'lab';
    }

    public function label(): string
    {
        return __('lab::lab.title');
    }

    public function defaults(): array
    {
        return [
            'listing_title' => ['en' => 'Laboratory Facilities', 'bn' => 'ল্যাবরেটরি সুবিধা'],
            'listing_text' => [
                'en' => 'Explore our state-of-the-art laboratories and research facilities across different departments',
                'bn' => 'বিভিন্ন ডিপার্টমেন্টের আধুনিক ল্যাবরেটরি ও গবেষণা সুবিধা ঘুরে দেখুন',
            ],
            'listing_image' => null,
            'department_hero' => ['en' => 'List of Labs under Department: :department', 'bn' => ':department ডিপার্টমেন্টের ল্যাবসমূহ'],
            'department_text' => ['en' => 'Explore our laboratory facilities and research centers', 'bn' => 'আমাদের ল্যাবরেটরি ও গবেষণা কেন্দ্রগুলো ঘুরে দেখুন'],
        ];
    }

    public function translatable(): array
    {
        return ['listing_title', 'listing_text', 'department_hero', 'department_text'];
    }

    public function media(): array
    {
        return ['listing_image'];
    }

    public function schema(): array
    {
        return [
            Section::make(__('lab::lab.settings.listing'))->description(__('lab::lab.settings.listing_help'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("listing_title.{$locale}")->label(__('lab::lab.settings.title'))->maxLength(150),
                    Textarea::make("listing_text.{$locale}")->label(__('lab::lab.settings.text'))->rows(3)->maxLength(1000),
                    TextInput::make("department_hero.{$locale}")->label(__('lab::lab.settings.department_hero'))->helperText(__('lab::lab.settings.department_hero_help'))->maxLength(250),
                    TextInput::make("department_text.{$locale}")->label(__('lab::lab.settings.department_text'))->maxLength(250),
                ]),
                MediaPicker::make('listing_image')->label(__('lab::lab.settings.image'))->image()->storeIds(),
            ]),
        ];
    }
}
