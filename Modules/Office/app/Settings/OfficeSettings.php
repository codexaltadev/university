<?php

namespace Modules\Office\Settings;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\LinkListField;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Offices: the Quick Links of an office page whose office has none of its own
 * (DIU's are the defaults); the title, text and picture of /support-contacts (DIU's "Support Contact Directory").
 * Read: link_list(settings('office.page_quick_links')), settings('office.support_title'), settings('office.support_subtitle'),
 * media_url(settings('office.support_image')).
 */
class OfficeSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'office';
    }

    public function label(): string
    {
        return __('office::office.plural');
    }

    public function defaults(): array
    {
        return [
            'page_quick_links' => [
                ['label' => ['en' => 'Notice', 'bn' => 'নোটিশ'], 'url' => '/notices'],
                ['label' => ['en' => 'Tuition Fees', 'bn' => 'টিউশন ফি'], 'url' => '/tuition-fees'],
                ['label' => ['en' => 'Waiver Calculator', 'bn' => 'ওয়েভার ক্যালকুলেটর'], 'url' => '/waiver-calculator'],
                ['label' => ['en' => 'Waiver & Scholarship', 'bn' => 'ওয়েভার ও স্কলারশিপ'], 'url' => '/scholarships'],
            ],
            'support_title' => ['en' => 'Support Contact Directory', 'bn' => 'সাপোর্ট যোগাযোগ তালিকা'],
            'support_subtitle' => [
                'en' => 'Connect with support staff and administrative personnel across all departments and offices',
                'bn' => 'সব বিভাগ ও অফিসের সাপোর্ট স্টাফ ও প্রশাসনিক কর্মকর্তাদের সাথে যোগাযোগ করুন',
            ],
            'support_image' => null,
        ];
    }

    public function translatable(): array
    {
        return ['support_title', 'support_subtitle'];
    }

    public function media(): array
    {
        return ['support_image'];
    }

    public function schema(): array
    {
        return [
            Section::make(__('office::office.settings.page_quick_links'))->description(__('office::office.settings.page_quick_links_help'))->compact()
                ->schema([LinkListField::make('page_quick_links')->hiddenLabel()]),
            Section::make(__('office::office.settings.support'))->description(__('office::office.settings.support_help'))->compact()
                ->schema([
                    TranslatableTabs::make()->schema(fn (string $locale): array => [
                        TextInput::make("support_title.{$locale}")->label(__('office::office.settings.support_title'))->maxLength(150),
                        Textarea::make("support_subtitle.{$locale}")->label(__('office::office.settings.support_subtitle'))->rows(2)->maxLength(300),
                    ]),
                    MediaPicker::make('support_image')->label(__('office::office.settings.support_image'))->image()->storeIds(),
                ]),
        ];
    }
}
