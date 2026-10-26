<?php

namespace Modules\Faq\Settings;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › FAQ tab (FR-SET-10, Module Guide §12): per page, "Was this helpful?", and (Phase 4.7f, DIU's /faq)
 * the page's title — its last word in colour — and line. Read: settings('faq.per_page') … (Support\FaqPage)
 */
class FaqSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'faq';
    }

    public function label(): string
    {
        return __('faq::faq.title');
    }

    public function defaults(): array
    {
        return [
            'per_page' => 20, 'show_helpful_buttons' => true,
            'title' => ['en' => 'Frequently Asked Questions', 'bn' => 'সচরাচর জিজ্ঞাসিত প্রশ্ন'],
            'text' => [
                'en' => 'Find answers to common questions about Daffodil International University. Browse through categories or search for specific topics.',
                'bn' => 'Daffodil International University নিয়ে সাধারণ প্রশ্নের উত্তর খুঁজুন। Category ধরে দেখুন বা নির্দিষ্ট বিষয় খুঁজুন।',
            ],
        ];
    }

    public function translatable(): array
    {
        return ['title', 'text'];
    }

    public function schema(): array
    {
        return [
            TextInput::make('per_page')->label(__('faq::faq.settings.per_page'))
                ->numeric()->integer()->minValue(5)->maxValue(100)->required(),
            Toggle::make('show_helpful_buttons')->label(__('faq::faq.settings.show_helpful_buttons')),
            TranslatableTabs::make()->schema(fn (string $locale): array => [
                TextInput::make("title.{$locale}")->label(__('faq::faq.settings.title'))->maxLength(150)->helperText(__('faq::faq.settings.title_help')),
                Textarea::make("text.{$locale}")->label(__('faq::faq.settings.text'))->rows(2)->maxLength(300),
            ]),
        ];
    }
}
