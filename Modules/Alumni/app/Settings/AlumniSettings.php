<?php

namespace Modules\Alumni\Settings;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Alumni: the alumni page's own title, text and picture, and DIU's "Join the
 * alumni portal" link (its alumni site; DIU's are the defaults). Read: settings('alumni.title'),
 * settings('alumni.text'), media_url(settings('alumni.image')), settings('alumni.portal_url'), settings('alumni.portal_label').
 */
class AlumniSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'alumni';
    }

    public function label(): string
    {
        return __('alumni::alumni.plural');
    }

    public function defaults(): array
    {
        return [
            'title' => ['en' => 'Meet Our Distinguished Alumni', 'bn' => 'আমাদের কৃতী প্রাক্তন শিক্ষার্থী'],
            'text' => [
                'en' => 'Discover the remarkable achievements of our graduates who are making an impact across industries and organizations worldwide.',
                'bn' => 'বিশ্বজুড়ে নানা শিল্প ও প্রতিষ্ঠানে অবদান রাখা আমাদের স্নাতকদের অর্জন জানুন।',
            ],
            'image' => null,
            'portal_url' => 'https://alumni.daffodilvarsity.edu.bd/',
            'portal_label' => ['en' => 'Join the alumni portal', 'bn' => 'Alumni portal-এ যোগ দিন'],
        ];
    }

    public function translatable(): array
    {
        return ['title', 'text', 'portal_label'];
    }

    public function media(): array
    {
        return ['image'];
    }

    public function schema(): array
    {
        return [
            Section::make(__('alumni::alumni.settings.page'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("title.{$locale}")->label(__('alumni::alumni.settings.title'))->maxLength(150),
                    Textarea::make("text.{$locale}")->label(__('alumni::alumni.settings.text'))->rows(2)->maxLength(500),
                    TextInput::make("portal_label.{$locale}")->label(__('alumni::alumni.settings.portal_label'))->maxLength(80),
                ]),
                MediaPicker::make('image')->label(__('alumni::alumni.settings.image'))->image()->storeIds(),
                TextInput::make('portal_url')->label(__('alumni::alumni.settings.portal_url'))->url()->maxLength(300)
                    ->helperText(__('alumni::alumni.settings.portal_url_help')),
            ]),
        ];
    }
}
