<?php

namespace Modules\Partners\Settings;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\LinkListField;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Partners: the titles and lines of /partners (DIU's /mps "Member Organization …")
 * and /student-discounts (DIU's "Discount Partners"), the picture on top and the Quick Links beside
 * the discount categories (DIU's are the defaults). Read: settings('partners.partners_title') …,
 * link_list(settings('partners.quick_links')).
 */
class PartnersSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'partners';
    }

    public function label(): string
    {
        return __('partners::partners.plural');
    }

    public function defaults(): array
    {
        return [
            'partners_title' => ['en' => 'Members & Partners', 'bn' => 'সদস্যপদ ও অংশীদার'],
            'partners_text' => ['en' => 'The organizations Daffodil International University belongs to and works with', 'bn' => 'যেসব প্রতিষ্ঠানের সদস্য ও যাদের সাথে ড্যাফোডিল ইন্টারন্যাশনাল ইউনিভার্সিটি কাজ করে'],
            'discounts_title' => ['en' => 'Discount Partners', 'bn' => 'ছাড়ের অংশীদার'],
            'discounts_text' => ['en' => 'Daffodil International University', 'bn' => 'ড্যাফোডিল ইন্টারন্যাশনাল ইউনিভার্সিটি'],
            'image' => null,
            'quick_links' => [
                ['label' => ['en' => 'Notice', 'bn' => 'নোটিশ'], 'url' => '/notices'],
                ['label' => ['en' => 'Tuition Fees', 'bn' => 'টিউশন ফি'], 'url' => '/tuition-fees'],
                ['label' => ['en' => 'Waiver Calculator', 'bn' => 'ওয়েভার ক্যালকুলেটর'], 'url' => '/waiver-calculator'],
                ['label' => ['en' => 'Waiver & Scholarship', 'bn' => 'ওয়েভার ও স্কলারশিপ'], 'url' => '/scholarships'],
            ],
        ];
    }

    public function translatable(): array
    {
        return ['partners_title', 'partners_text', 'discounts_title', 'discounts_text'];
    }

    public function media(): array
    {
        return ['image'];
    }

    public function schema(): array
    {
        return [
            Section::make(__('partners::partners.settings.pages'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("partners_title.{$locale}")->label(__('partners::partners.settings.partners_title'))->maxLength(150),
                    Textarea::make("partners_text.{$locale}")->label(__('partners::partners.settings.partners_text'))->rows(2)->maxLength(300),
                    TextInput::make("discounts_title.{$locale}")->label(__('partners::partners.settings.discounts_title'))->maxLength(150),
                    Textarea::make("discounts_text.{$locale}")->label(__('partners::partners.settings.discounts_text'))->rows(2)->maxLength(300),
                ]),
                MediaPicker::make('image')->label(__('partners::partners.settings.image'))->image()->storeIds(),
            ]),
            Section::make(__('partners::partners.settings.quick_links'))->description(__('partners::partners.settings.quick_links_help'))->compact()
                ->schema([LinkListField::make('quick_links')->hiddenLabel()]),
        ];
    }
}
