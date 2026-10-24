<?php

namespace Modules\Faculty\Settings;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\LinkListField;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Faculties: the /faculties page's own text — title, text and picture on top,
 * the links beside the faculties (Faculty Members …) and its Quick Links (DIU's are the defaults).
 * Read: settings('faculty.listing_title'), media_url(settings('faculty.listing_image')),
 * link_list(settings('faculty.side_links')), link_list(settings('faculty.quick_links')).
 */
class FacultySettings extends ModuleSettings
{
    public function group(): string
    {
        return 'faculty';
    }

    public function label(): string
    {
        return __('faculty::faculty.plural');
    }

    public function defaults(): array
    {
        return [
            'listing_title' => null,
            'listing_text' => null,
            'listing_image' => null,
            'side_links' => [
                ['label' => ['en' => 'Faculty Members', 'bn' => 'শিক্ষকমণ্ডলী'], 'url' => '/faculty-members'],
                ['label' => ['en' => 'International Advisor', 'bn' => 'আন্তর্জাতিক উপদেষ্টা'], 'url' => '/article/international-adviser'],
                ['label' => ['en' => 'Visiting Professors', 'bn' => 'ভিজিটিং প্রফেসর'], 'url' => '/article/visiting-professors'],
            ],
            'quick_links' => [
                ['label' => ['en' => 'Admission Requirements', 'bn' => 'ভর্তির শর্ত'], 'url' => 'https://webbackend.daffodilvarsity.edu.bd/photos/pdf/payment-guidelines.pdf'],
                ['label' => ['en' => 'Transfer Credits', 'bn' => 'ক্রেডিট ট্রান্সফার'], 'url' => '/article/credit-transfer'],
                ['label' => ['en' => 'Waiver & Scholarship', 'bn' => 'ওয়েভার ও স্কলারশিপ'], 'url' => '/scholarships'],
            ],
        ];
    }

    public function translatable(): array
    {
        return ['listing_title', 'listing_text'];
    }

    public function media(): array
    {
        return ['listing_image'];
    }

    public function schema(): array
    {
        return [
            Section::make(__('faculty::faculty.settings.listing'))->description(__('faculty::faculty.settings.listing_help'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("listing_title.{$locale}")->label(__('faculty::faculty.settings.title'))->maxLength(150),
                    Textarea::make("listing_text.{$locale}")->label(__('faculty::faculty.settings.text'))->rows(3)->maxLength(1000),
                ]),
                MediaPicker::make('listing_image')->label(__('faculty::faculty.settings.image'))->image()->storeIds(),
            ]),
            Section::make(__('faculty::faculty.settings.side_links'))->description(__('faculty::faculty.settings.side_links_help'))->compact()
                ->schema([LinkListField::make('side_links')->hiddenLabel()]),
            Section::make(__('faculty::faculty.fields.quick_links'))->compact()
                ->schema([LinkListField::make('quick_links')->hiddenLabel()]),
        ];
    }
}
