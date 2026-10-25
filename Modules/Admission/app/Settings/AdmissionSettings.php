<?php

namespace Modules\Admission\Settings;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\LinkListField;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Admission (DB-03 v1.5 §10): where "Apply Now" and "Request for
 * Information" go (FR-ADM-04), the WhatsApp group, the text on /admission/result, and (Phase 4.7b-3,
 * DIU's /admission-contact and /admission-test-result) the Admission Office's contact — name, address,
 * helpline, email, map — and the result page's links and picture.
 * Read: settings('admission.apply_url') … (Support\AdmissionPage)
 */
class AdmissionSettings extends ModuleSettings
{
    /** Google Maps "Embed a map" addresses only (the page shows it in a frame) */
    public const MAP_PATTERN = '#^https://www\.google\.com/maps/embed\?#';

    public function group(): string
    {
        return 'admission';
    }

    public function label(): string
    {
        return __('admission::admission.title');
    }

    public function defaults(): array
    {
        return [
            'apply_url' => null, 'info_request_url' => null, 'whatsapp_group_url' => null, 'result_page_intro' => null,
            'office_name' => ['en' => 'Daffodil International University', 'bn' => 'ড্যাফোডিল ইন্টারন্যাশনাল ইউনিভার্সিটি'],
            'office_address' => ['en' => 'Daffodil Smart City, Birulia, Savar, Dhaka-1216', 'bn' => 'ড্যাফোডিল স্মার্ট সিটি, বিরুলিয়া, সাভার, ঢাকা-১২১৬'],
            'helpline' => null,
            'email' => null,
            'map_url' => null,
            'result_links' => [
                ['label' => ['en' => 'Admission', 'bn' => 'ভর্তি'], 'url' => '/admission'],
                ['label' => ['en' => 'Programs', 'bn' => 'Program'], 'url' => '/programs'],
                ['label' => ['en' => 'Admission Contact', 'bn' => 'ভর্তি যোগাযোগ'], 'url' => '/admission/contact'],
                ['label' => ['en' => 'Fees and Funding', 'bn' => 'Fee ও অর্থায়ন'], 'url' => '/tuition-fees'],
            ],
            'result_image' => null,
        ];
    }

    public function translatable(): array
    {
        return ['result_page_intro', 'office_name', 'office_address'];
    }

    public function media(): array
    {
        return ['result_image'];
    }

    public function schema(): array
    {
        return [
            TextInput::make('apply_url')->label(__('admission::admission.settings.apply_url'))->url()->maxLength(2048)
                ->helperText(__('admission::admission.settings.apply_url_help')),
            TextInput::make('info_request_url')->label(__('admission::admission.settings.info_request_url'))
                ->regex('#^(https?://|/)#')->maxLength(2048),
            TextInput::make('whatsapp_group_url')->label(__('admission::admission.settings.whatsapp_group_url'))->url()->maxLength(2048),
            Section::make(__('admission::admission.settings.office'))->description(__('admission::admission.settings.office_help'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("office_name.{$locale}")->label(__('admission::admission.settings.office_name'))->maxLength(150),
                    TextInput::make("office_address.{$locale}")->label(__('admission::admission.settings.office_address'))->maxLength(300),
                ]),
                TextInput::make('helpline')->label(__('admission::admission.settings.helpline'))->tel()->maxLength(30),
                TextInput::make('email')->label(__('admission::admission.settings.email'))->email()->maxLength(191),
                TextInput::make('map_url')->label(__('admission::admission.settings.map_url'))->url()->regex(self::MAP_PATTERN)->maxLength(2048)
                    ->helperText(__('admission::admission.settings.map_url_help')),
            ]),
            Section::make(__('admission::admission.result_page.title'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    RichEditor::make("result_page_intro.{$locale}")->label(__('admission::admission.settings.result_page_intro'))->fileAttachments(false),
                ]),
                LinkListField::make('result_links')->label(__('admission::admission.settings.result_links')),
                MediaPicker::make('result_image')->label(__('admission::admission.settings.result_image'))->image()->storeIds(),
            ]),
        ];
    }
}
