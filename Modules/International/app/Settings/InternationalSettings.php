<?php

namespace Modules\International\Settings;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\LinkListField;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › International (FR-INTL-03): an expired agreement is hidden or marked
 * "Expired"; how many days before its end the admins are told; the pages' titles, lines and
 * pictures (DIU's are the defaults); the links beside the partner list ("Browse by Section",
 * DIU's international pages) and the Quick Links.
 * Read: settings('international.expired'), settings('international.home_title') …,
 * link_list(settings('international.side_links')), link_list(settings('international.quick_links')).
 */
class InternationalSettings extends ModuleSettings
{
    /** page => [title, line] defaults (en, bn) */
    public const PAGES = [
        'home' => [['en' => 'Your Global Education Journey Starts Here', 'bn' => 'বিশ্বমানের শিক্ষার যাত্রা শুরু এখানেই'], ['en' => 'Discover a welcoming campus, world-class academics, and a vibrant international community.', 'bn' => 'স্বাগত জানানো ক্যাম্পাস, বিশ্বমানের শিক্ষা ও প্রাণবন্ত আন্তর্জাতিক সমাজ।']],
        'partners' => [['en' => 'International Linkage', 'bn' => 'আন্তর্জাতিক সংযোগ'], ['en' => 'Universities and institutions we work with around the world', 'bn' => 'বিশ্বজুড়ে যেসব বিশ্ববিদ্যালয় ও প্রতিষ্ঠানের সাথে আমরা কাজ করি']],
        'conferences' => [['en' => 'International Conferences', 'bn' => 'আন্তর্জাতিক সম্মেলন'], ['en' => 'Conferences hosted and joined by Daffodil International University', 'bn' => 'ড্যাফোডিল ইন্টারন্যাশনাল ইউনিভার্সিটির আয়োজিত ও অংশ নেওয়া সম্মেলন']],
        'visitors' => [['en' => 'Distinguished Visitors', 'bn' => 'বিশিষ্ট অতিথি'], ['en' => 'Guests from Bangladesh and across the globe on their visit to Daffodil International University', 'bn' => 'দেশ-বিদেশের অতিথিরা ড্যাফোডিল ইন্টারন্যাশনাল ইউনিভার্সিটি ঘুরে যা বলেছেন']],
    ];

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
        $pages = [];
        foreach (self::PAGES as $page => [$title, $text]) {
            $pages += ["{$page}_title" => $title, "{$page}_text" => $text, "{$page}_image" => null];
        }

        return [
            'expired' => 'hide',
            'notify_days' => 60,
            ...$pages,
            'side_links' => [
                ['label' => ['en' => 'Introduction', 'bn' => 'পরিচিতি'], 'url' => '/introduction'],
                ['label' => ['en' => 'International Contact', 'bn' => 'আন্তর্জাতিক যোগাযোগ'], 'url' => '/international-contact'],
                ['label' => ['en' => 'International Alumni', 'bn' => 'আন্তর্জাতিক প্রাক্তন শিক্ষার্থী'], 'url' => '/international-alumni'],
                ['label' => ['en' => 'International Event', 'bn' => 'আন্তর্জাতিক অনুষ্ঠান'], 'url' => '/international-event'],
                ['label' => ['en' => 'Eligibility', 'bn' => 'যোগ্যতা'], 'url' => '/admission-eligibility-international'],
                ['label' => ['en' => 'Tuition Fees', 'bn' => 'টিউশন ফি'], 'url' => '/int-tuition-fees'],
                ['label' => ['en' => 'Exchange Policy', 'bn' => 'বিনিময় নীতি'], 'url' => '/exchange-policy'],
            ],
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
        return collect(array_keys(self::PAGES))->flatMap(fn (string $p): array => ["{$p}_title", "{$p}_text"])->all();
    }

    public function media(): array
    {
        return collect(array_keys(self::PAGES))->map(fn (string $p): string => "{$p}_image")->all();
    }

    public function schema(): array
    {
        return [
            Radio::make('expired')->label(__('international::international.settings.expired'))->inline()
                ->options(['hide' => __('international::international.settings.hide'), 'label' => __('international::international.settings.label')]),
            TextInput::make('notify_days')->label(__('international::international.settings.notify_days'))->integer()->minValue(1)->maxValue(365)->dehydrateStateUsing(fn ($state): int => (int) $state),
            ...collect(array_keys(self::PAGES))->map(fn (string $page): Section => Section::make(__("international::international.settings.page_{$page}"))
                ->description($page === 'visitors' ? __('international::international.settings.visitors_help') : null)->compact()->collapsible()->schema([
                    TranslatableTabs::make()->schema(fn (string $locale): array => [
                        TextInput::make("{$page}_title.{$locale}")->label(__('international::international.settings.title'))->maxLength(150),
                        Textarea::make("{$page}_text.{$locale}")->label(__('international::international.settings.text'))->rows(2)->maxLength(500),
                    ]),
                    MediaPicker::make("{$page}_image")->label(__('international::international.settings.image'))->image()->storeIds(),
                ]))->all(),
            Section::make(__('international::international.settings.side_links'))->description(__('international::international.settings.side_links_help'))->compact()->collapsible()
                ->schema([LinkListField::make('side_links')->hiddenLabel()]),
            Section::make(__('international::international.settings.quick_links'))->compact()->collapsible()
                ->schema([LinkListField::make('quick_links')->hiddenLabel()]),
        ];
    }
}
