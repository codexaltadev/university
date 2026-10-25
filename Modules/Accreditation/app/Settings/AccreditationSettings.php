<?php

namespace Modules\Accreditation\Settings;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\LinkListField;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Accreditation & Ranking (FR-ACR-02): an expired accreditation is hidden or
 * shown as "Expired"; how many days before its end the admins are told.
 * Read: settings('accreditation.expired'), settings('accreditation.notify_days').
 */
class AccreditationSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'accreditation';
    }

    public function label(): string
    {
        return __('accreditation::accreditation.title');
    }

    public function defaults(): array
    {
        return [
            'expired' => 'hide', 'notify_days' => 60,
            // the pages' texts (DIU's /rankings are the defaults), the picture on top, Quick Links under the rankings
            'rankings_title' => ['en' => 'Global Rankings & Achievements', 'bn' => 'বৈশ্বিক র‍্যাংকিং ও অর্জন'],
            'rankings_text' => ['en' => 'Recognized among the top universities in Bangladesh and beyond for excellence in education, research, and innovation.', 'bn' => 'শিক্ষা, গবেষণা ও উদ্ভাবনে দেশ ও বিদেশে শীর্ষ বিশ্ববিদ্যালয়গুলোর একটি।'],
            'accreditations_title' => ['en' => 'Accreditations & Memberships', 'bn' => 'অ্যাক্রেডিটেশন ও সদস্যপদ'],
            'accreditations_text' => ['en' => 'Quality assured by national and international bodies', 'bn' => 'দেশি-বিদেশি প্রতিষ্ঠানের মান-স্বীকৃতি'],
            'image' => null,
            'quick_links' => [
                ['label' => ['en' => 'Tuition Fees', 'bn' => 'টিউশন ফি'], 'url' => '/tuition-fees'],
                ['label' => ['en' => 'Transfer Credits', 'bn' => 'ক্রেডিট ট্রান্সফার'], 'url' => '/article/credit-transfer'],
                ['label' => ['en' => 'Waiver & Scholarship', 'bn' => 'ওয়েভার ও স্কলারশিপ'], 'url' => '/scholarships'],
            ],
        ];
    }

    public function translatable(): array
    {
        return ['rankings_title', 'rankings_text', 'accreditations_title', 'accreditations_text'];
    }

    public function media(): array
    {
        return ['image'];
    }

    public function schema(): array
    {
        return [
            Radio::make('expired')->label(__('accreditation::accreditation.settings.expired'))->inline()
                ->options(['hide' => __('accreditation::accreditation.settings.hide'), 'label' => __('accreditation::accreditation.settings.label')]),
            TextInput::make('notify_days')->label(__('accreditation::accreditation.settings.notify_days'))->integer()->minValue(1)->maxValue(365)->dehydrateStateUsing(fn ($state): int => (int) $state),
            Section::make(__('accreditation::accreditation.settings.pages'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("rankings_title.{$locale}")->label(__('accreditation::accreditation.settings.rankings_title'))->maxLength(150),
                    Textarea::make("rankings_text.{$locale}")->label(__('accreditation::accreditation.settings.rankings_text'))->rows(2)->maxLength(300),
                    TextInput::make("accreditations_title.{$locale}")->label(__('accreditation::accreditation.settings.accreditations_title'))->maxLength(150),
                    Textarea::make("accreditations_text.{$locale}")->label(__('accreditation::accreditation.settings.accreditations_text'))->rows(2)->maxLength(300),
                ]),
                MediaPicker::make('image')->label(__('accreditation::accreditation.settings.image'))->image()->storeIds(),
            ]),
            Section::make(__('accreditation::accreditation.settings.quick_links'))->description(__('accreditation::accreditation.settings.quick_links_help'))->compact()
                ->schema([LinkListField::make('quick_links')->hiddenLabel()]),
        ];
    }
}
