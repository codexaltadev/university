<?php

namespace Modules\Scholarship\Settings;

use Filament\Forms\Components\RichEditor;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Scholarships: the text above and below /scholarships (DB-03 §11.3 v1.4).
 * Read: settings('scholarship.intro'), settings('scholarship.outro').
 */
class ScholarshipSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'scholarship';
    }

    public function label(): string
    {
        return __('scholarship::scholarship.plural');
    }

    public function defaults(): array
    {
        return ['intro' => null, 'outro' => null];
    }

    public function translatable(): array
    {
        return ['intro', 'outro'];
    }

    public function schema(): array
    {
        return [
            TranslatableTabs::make()->schema(fn (string $locale): array => [
                RichEditor::make("intro.{$locale}")->label(__('scholarship::scholarship.settings.intro'))->fileAttachments(false),
                RichEditor::make("outro.{$locale}")->label(__('scholarship::scholarship.settings.outro'))->fileAttachments(false),
            ]),
        ];
    }
}
