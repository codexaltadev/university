<?php

namespace Modules\Tuition\Settings;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\LinkListField;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Tuition Fees (Phase 4.7c, DIU's /tuition-fees): the page's title, line and picture on top, the
 * notice above the tables (DIU: the free laptop), the Quick Links under each faculty's table and the "Need
 * Assistance?" links. Read: settings('tuition.title') … (Support\TuitionPage)
 */
class TuitionSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'tuition';
    }

    public function label(): string
    {
        return __('tuition::tuition.tuition_fees');
    }

    public function defaults(): array
    {
        return [
            'title' => ['en' => 'Tuition & Fees at a Glance', 'bn' => 'এক নজরে Tuition ও Fee'],
            'text' => [
                'en' => 'Understand the cost of your DIU education—and the many ways we help you manage it. DIU provides transparent, program-specific tuition information so you can plan with confidence. Select your degree level below for a detailed breakdown, then explore flexible payment plans, scholarships, and real-time waiver calculators.',
                'bn' => 'আপনার পড়াশোনার খরচ এবং তা সামলাতে আমাদের নানা সুবিধা জানুন। প্রতিটি program-এর tuition স্পষ্টভাবে দেওয়া আছে, যাতে আপনি নিশ্চিন্তে পরিকল্পনা করতে পারেন।',
            ],
            'image' => null,
            'notice' => null,
            'quick_links' => [
                ['label' => ['en' => 'Admission', 'bn' => 'ভর্তি'], 'url' => '/admission'],
                ['label' => ['en' => 'Tuition Fee Calculator', 'bn' => 'Tuition Fee Calculator'], 'url' => '/waiver-calculator'],
                ['label' => ['en' => 'Waiver & Scholarship', 'bn' => 'Waiver ও Scholarship'], 'url' => '/scholarships'],
            ],
            'help_links' => [
                ['label' => ['en' => 'Estimate My Fees', 'bn' => 'আমার খরচ হিসাব করুন'], 'url' => '/waiver-calculator'],
                ['label' => ['en' => 'Waiver & Scholarship', 'bn' => 'Waiver ও Scholarship'], 'url' => '/scholarships'],
            ],
        ];
    }

    public function translatable(): array
    {
        return ['title', 'text', 'notice'];
    }

    public function media(): array
    {
        return ['image'];
    }

    public function schema(): array
    {
        return [
            Section::make(__('tuition::tuition.settings.page'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("title.{$locale}")->label(__('tuition::tuition.settings.title'))->maxLength(150),
                    Textarea::make("text.{$locale}")->label(__('tuition::tuition.settings.text'))->rows(3)->maxLength(500),
                    TextInput::make("notice.{$locale}")->label(__('tuition::tuition.settings.notice'))->maxLength(300)
                        ->helperText(__('tuition::tuition.settings.notice_help')),
                ]),
                MediaPicker::make('image')->label(__('tuition::tuition.settings.image'))->image()->storeIds(),
            ]),
            Section::make(__('tuition::tuition.settings.quick_links'))->compact()->schema([LinkListField::make('quick_links')->hiddenLabel()]),
            Section::make(__('tuition::tuition.settings.help_links'))->compact()->schema([LinkListField::make('help_links')->hiddenLabel()]),
        ];
    }
}
