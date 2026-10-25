<?php

namespace Modules\Documents\Settings;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Downloads: the downloads page's own title and text (DIU's "Digital Brochures"
 * are the defaults). Read: settings('documents.title'), settings('documents.text').
 */
class DocumentsSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'documents';
    }

    public function label(): string
    {
        return __('documents::documents.plural');
    }

    public function defaults(): array
    {
        return [
            'title' => ['en' => 'Downloads', 'bn' => 'ডাউনলোড'],
            'text' => ['en' => 'Browse our interactive flipbooks and downloadable guides', 'bn' => 'ফ্লিপবুক, প্রসপেক্টাস, নির্দেশিকা ও অন্যান্য ডকুমেন্ট'],
        ];
    }

    public function translatable(): array
    {
        return ['title', 'text'];
    }

    public function schema(): array
    {
        return [
            Section::make(__('documents::documents.settings.page'))->description(__('documents::documents.settings.page_help'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("title.{$locale}")->label(__('documents::documents.settings.title'))->maxLength(150),
                    Textarea::make("text.{$locale}")->label(__('documents::documents.settings.text'))->rows(2)->maxLength(500),
                ]),
            ]),
        ];
    }
}
