<?php

namespace Modules\Csr\Settings;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Social Responsibility: the texts of DIU's /csr-activities page — title and line on
 * top, the impact numbers, the headings and lines of "Our Approach…", "Where We Create Impact",
 * "Stories of Change", the picture on top and the CSR Report (a PDF from the Media Library).
 * DIU's are the defaults. Read: settings('csr.title') …, media_url(settings('csr.report')).
 */
class CsrSettings extends ModuleSettings
{
    public const TEXTS = ['title', 'subtitle', 'approach_title', 'approach_text', 'impact_title', 'impact_text', 'stories_title', 'stories_text', 'report_label'];

    public function group(): string
    {
        return 'csr';
    }

    public function label(): string
    {
        return __('csr::csr.plural');
    }

    public function defaults(): array
    {
        return [
            'title' => ['en' => 'Corporate Social Responsibility (CSR) Activities', 'bn' => 'সামাজিক দায়বদ্ধতা (CSR) কার্যক্রম'],
            'subtitle' => ['en' => 'Making a positive impact in our communities through dedicated humanitarian initiatives and social welfare programs', 'bn' => 'মানবিক উদ্যোগ ও সমাজকল্যাণ কর্মসূচির মাধ্যমে সমাজে ইতিবাচক পরিবর্তন'],
            'stats' => [
                ['value' => '50,000+', 'label' => ['en' => 'Individuals Supported', 'bn' => 'জন মানুষ সহায়তা পেয়েছেন']],
                ['value' => '120+', 'label' => ['en' => 'Programs Conducted', 'bn' => 'কর্মসূচি']],
                ['value' => '25+', 'label' => ['en' => 'Communities Impacted', 'bn' => 'জনগোষ্ঠী']],
            ],
            'approach_title' => ['en' => 'Our Approach to Social Responsibility', 'bn' => 'সামাজিক দায়বদ্ধতায় আমাদের পথ'],
            'approach_text' => ['en' => 'A structured, impact-driven framework that integrates education, innovation, and community development.', 'bn' => 'শিক্ষা, উদ্ভাবন ও সমাজ উন্নয়নকে একসাথে নিয়ে একটি সুসংগঠিত কাঠামো।'],
            'impact_title' => ['en' => 'Where We Create Impact', 'bn' => 'যেখানে আমরা পরিবর্তন আনি'],
            'impact_text' => ['en' => 'Our initiatives are organised into strategic focus areas to maximise effectiveness and scalability.', 'bn' => 'আমাদের উদ্যোগগুলো কয়েকটি মূল ক্ষেত্রে সাজানো।'],
            'stories_title' => ['en' => 'Stories of Change', 'bn' => 'পরিবর্তনের গল্প'],
            'stories_text' => ['en' => 'Real initiatives creating measurable outcomes in communities.', 'bn' => 'সমাজে বাস্তব ফল আনা উদ্যোগগুলো।'],
            'report_label' => ['en' => 'CSR Report', 'bn' => 'CSR প্রতিবেদন'],
            'report' => null,
            'image' => null,
        ];
    }

    public function translatable(): array
    {
        return self::TEXTS;
    }

    public function media(): array
    {
        return ['report', 'image'];
    }

    public function schema(): array
    {
        return [
            Section::make(__('csr::csr.settings.page'))->description(__('csr::csr.settings.page_help'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("title.{$locale}")->label(__('csr::csr.settings.title'))->maxLength(150),
                    Textarea::make("subtitle.{$locale}")->label(__('csr::csr.settings.subtitle'))->rows(2)->maxLength(300),
                    Grid::make(2)->schema([
                        TextInput::make("approach_title.{$locale}")->label(__('csr::csr.settings.approach_title'))->maxLength(150),
                        TextInput::make("approach_text.{$locale}")->label(__('csr::csr.settings.approach_text'))->maxLength(300),
                        TextInput::make("impact_title.{$locale}")->label(__('csr::csr.settings.impact_title'))->maxLength(150),
                        TextInput::make("impact_text.{$locale}")->label(__('csr::csr.settings.impact_text'))->maxLength(300),
                        TextInput::make("stories_title.{$locale}")->label(__('csr::csr.settings.stories_title'))->maxLength(150),
                        TextInput::make("stories_text.{$locale}")->label(__('csr::csr.settings.stories_text'))->maxLength(300),
                    ]),
                    TextInput::make("report_label.{$locale}")->label(__('csr::csr.settings.report_label'))->maxLength(60),
                ]),
                MediaPicker::make('image')->label(__('csr::csr.settings.image'))->image()->storeIds(),
                MediaPicker::make('report')->label(__('csr::csr.settings.report'))->storeIds(),
            ]),
            Section::make(__('csr::csr.settings.stats'))->description(__('csr::csr.settings.stats_help'))->compact()->schema([
                Repeater::make('stats')->hiddenLabel()->schema([
                    Grid::make(2)->schema([
                        TextInput::make('value')->label(__('csr::csr.settings.stat_value'))->required()->maxLength(20),
                        ...TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                            TextInput::make("label.{$locale}")->label(__('csr::csr.settings.stat_label')." ({$language})")->required($isDefault)->maxLength(60),
                        ]),
                    ]),
                ])->reorderable()->maxItems(6)->defaultItems(0)
                    ->itemLabel(fn (array $state): ?string => trim(($state['value'] ?? '').' '.collect($state['label'] ?? [])->first(fn ($v) => filled($v)))),
            ]),
        ];
    }
}
