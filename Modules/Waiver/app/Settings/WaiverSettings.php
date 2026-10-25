<?php

namespace Modules\Waiver\Settings;

use App\Models\User;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Waiver Calculator tab: how matching waiver rules combine (FR-FEE-07), the
 * /waiver-calculator page's title, line and picture on top, and its "Important Notice" — the
 * calculator's disclaimer (FR-FEE-08) with a link. Read: settings('waiver.waiver_mode'),
 * settings('waiver.disclaimer'), settings('waiver.title') … (Support\CalculatorPage).
 */
class WaiverSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'waiver';
    }

    public function label(): string
    {
        return __('waiver::waiver.calculator.title');
    }

    public function defaults(): array
    {
        return [
            'waiver_mode' => 'highest',
            'title' => ['en' => 'Calculate Your Fees', 'bn' => 'আপনার খরচ হিসাব করুন'],
            'text' => ['en' => 'Discover your tuition fees with applicable waivers and scholarships. Get an instant breakdown of your admission payment and total savings', 'bn' => 'প্রযোজ্য waiver ও scholarship-সহ আপনার tuition fee জানুন। ভর্তির সময়ের payment ও মোট সাশ্রয়ের হিসাব সঙ্গে সঙ্গে দেখুন'],
            'image' => null,
            'notice_title' => ['en' => 'Important Notice', 'bn' => 'গুরুত্বপূর্ণ নোটিশ'],
            'disclaimer' => [
                'en' => 'This waiver calculation may vary based on the actual situation and necessary documents. Additionally, there may be a certain SGPA requirement to maintain the provided waiver. To know detailed information about waiver and scholarship, please visit our comprehensive guide.',
                'bn' => 'প্রকৃত অবস্থা ও প্রয়োজনীয় কাগজপত্র অনুযায়ী এই waiver-এর হিসাব ভিন্ন হতে পারে। এছাড়া প্রদত্ত waiver বজায় রাখতে নির্দিষ্ট SGPA লাগতে পারে। Waiver ও scholarship-এর বিস্তারিত জানতে আমাদের নির্দেশিকা দেখুন।',
            ],
            'notice_url' => '/scholarships',
            'notice_label' => ['en' => 'View Details', 'bn' => 'বিস্তারিত দেখুন'],
        ];
    }

    public function translatable(): array
    {
        return ['title', 'text', 'notice_title', 'disclaimer', 'notice_label'];
    }

    public function media(): array
    {
        return ['image'];
    }

    /** The Waiver Office's ("Manage:WaiverSettings"), also in Academic › Waiver Calculator › Settings. */
    public function canAccess(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('Manage:WaiverSettings');
    }

    public function schema(): array
    {
        return [
            Radio::make('waiver_mode')->label(__('waiver::waiver.settings.waiver_mode'))->required()
                ->options(['highest' => __('waiver::waiver.settings.mode_highest'), 'additive' => __('waiver::waiver.settings.mode_additive')]),
            Section::make(__('waiver::waiver.settings.page'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("title.{$locale}")->label(__('waiver::waiver.settings.page_title'))->maxLength(150),
                    Textarea::make("text.{$locale}")->label(__('waiver::waiver.settings.text'))->rows(2)->maxLength(300),
                    TextInput::make("notice_title.{$locale}")->label(__('waiver::waiver.settings.notice_title'))->maxLength(100),
                    Textarea::make("disclaimer.{$locale}")->label(__('waiver::waiver.settings.disclaimer'))->rows(3)->maxLength(1000)
                        ->helperText(__('waiver::waiver.settings.disclaimer_help')),
                    TextInput::make("notice_label.{$locale}")->label(__('waiver::waiver.settings.notice_label'))->maxLength(50),
                ]),
                TextInput::make('notice_url')->label(__('waiver::waiver.settings.notice_url'))->maxLength(255)->placeholder('/scholarships'),
                MediaPicker::make('image')->label(__('waiver::waiver.settings.image'))->image()->storeIds(),
            ]),
        ];
    }
}
