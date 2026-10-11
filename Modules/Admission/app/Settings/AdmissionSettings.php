<?php

namespace Modules\Admission\Settings;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Admission (DB-03 v1.5 §10): where "Apply Now" and "Request for
 * Information" go (FR-ADM-04), the WhatsApp group, the text on /admission/result.
 * Read: settings('admission.apply_url') …
 */
class AdmissionSettings extends ModuleSettings
{
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
        return ['apply_url' => null, 'info_request_url' => null, 'whatsapp_group_url' => null, 'result_page_intro' => null];
    }

    public function translatable(): array
    {
        return ['result_page_intro'];
    }

    public function schema(): array
    {
        return [
            TextInput::make('apply_url')->label(__('admission::admission.settings.apply_url'))->url()->maxLength(2048)
                ->helperText(__('admission::admission.settings.apply_url_help')),
            TextInput::make('info_request_url')->label(__('admission::admission.settings.info_request_url'))
                ->regex('#^(https?://|/)#')->maxLength(2048),
            TextInput::make('whatsapp_group_url')->label(__('admission::admission.settings.whatsapp_group_url'))->url()->maxLength(2048),
            TranslatableTabs::make()->schema(fn (string $locale): array => [
                RichEditor::make("result_page_intro.{$locale}")->label(__('admission::admission.settings.result_page_intro'))->fileAttachments(false),
            ]),
        ];
    }
}
