<?php

namespace Modules\Program\Settings;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\LinkListField;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Programs: the /programs page's own text — title, text and picture on top,
 * the links under the faculties (Institutional Programs …), its Quick Links — and the Quick Links of
 * a program page whose program has none of its own.
 * DIU's are the defaults. Read with settings('program.…'), media_url(), link_list().
 */
class ProgramSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'program';
    }

    public function label(): string
    {
        return __('program::program.plural');
    }

    public function defaults(): array
    {
        return [
            'listing_title' => null,
            'listing_text' => null,
            'listing_image' => null,
            'side_links' => [
                ['label' => ['en' => 'Institutional Programs', 'bn' => 'প্রাতিষ্ঠানিক প্রোগ্রাম'], 'url' => '/programs/institutional-programs'],
            ],
            'quick_links' => [
                ['label' => ['en' => 'Tuition Fees', 'bn' => 'টিউশন ফি'], 'url' => '/tuition-fees'],
                ['label' => ['en' => 'Waiver & Scholarship', 'bn' => 'ওয়েভার ও স্কলারশিপ'], 'url' => '/scholarships'],
                ['label' => ['en' => 'Intl. Tuition Fees', 'bn' => 'আন্তর্জাতিক টিউশন ফি'], 'url' => '/tuition-fees?category=international-tuition'],
                ['label' => ['en' => 'Intl. Scholarship', 'bn' => 'আন্তর্জাতিক স্কলারশিপ'], 'url' => '/scholarships'],
                ['label' => ['en' => 'Admission Notice', 'bn' => 'ভর্তি বিজ্ঞপ্তি'], 'url' => '/admission'],
                ['label' => ['en' => 'Rules and Regulation', 'bn' => 'নিয়মকানুন'], 'url' => '/article/rules-and-regulation'],
            ],
            'page_quick_links' => [
                ['label' => ['en' => 'Notice', 'bn' => 'নোটিশ'], 'url' => '/notices'],
                ['label' => ['en' => 'Waiver & Scholarship', 'bn' => 'ওয়েভার ও স্কলারশিপ'], 'url' => '/scholarships'],
                ['label' => ['en' => 'Tuition Fees', 'bn' => 'টিউশন ফি'], 'url' => '/tuition-fees'],
                ['label' => ['en' => 'Tuition Fee Calculator', 'bn' => 'টিউশন ফি ক্যালকুলেটর'], 'url' => '/waiver-calculator'],
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
            Section::make(__('program::program.settings.listing'))->description(__('program::program.settings.listing_help'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("listing_title.{$locale}")->label(__('program::program.settings.title'))->maxLength(150),
                    Textarea::make("listing_text.{$locale}")->label(__('program::program.settings.text'))->rows(3)->maxLength(1000),
                ]),
                MediaPicker::make('listing_image')->label(__('program::program.settings.image'))->image()->storeIds(),
            ]),
            Section::make(__('program::program.settings.side_links'))->description(__('program::program.settings.side_links_help'))->compact()
                ->schema([LinkListField::make('side_links')->hiddenLabel()]),
            Section::make(__('program::program.fields.quick_links'))->compact()
                ->schema([LinkListField::make('quick_links')->hiddenLabel()]),
            Section::make(__('program::program.settings.page_quick_links'))->description(__('program::program.settings.page_quick_links_help'))->compact()
                ->schema([LinkListField::make('page_quick_links')->hiddenLabel()]),
        ];
    }
}
