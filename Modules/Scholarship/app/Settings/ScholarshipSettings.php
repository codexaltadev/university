<?php

namespace Modules\Scholarship\Settings;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\LinkListField;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Scholarships: the text above and below /scholarships (DB-03 §11.3 v1.4), and (Phase 4.7d, DIU's
 * /scholarship) the title, line and picture on top, the "Browse by Section" links beside the scholarships (DIU's named
 * scholarships, FAQ …) and the Quick Links under them. Read: settings('scholarship.intro') … (Support\ScholarshipPage)
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
        return [
            'intro' => null, 'outro' => null,
            'title' => ['en' => 'Scholarships', 'bn' => 'Scholarship'],
            'text' => ['en' => 'Explore various scholarship opportunities at DIU', 'bn' => 'DIU-র নানা scholarship-এর সুযোগ দেখুন'],
            'image' => null,
            'side_links' => [],
            'quick_links' => [
                ['label' => ['en' => 'Tuition Fees', 'bn' => 'Tuition Fee'], 'url' => '/tuition-fees'],
                ['label' => ['en' => 'Waiver Calculator', 'bn' => 'Waiver Calculator'], 'url' => '/waiver-calculator'],
            ],
        ];
    }

    public function translatable(): array
    {
        return ['intro', 'outro', 'title', 'text'];
    }

    public function media(): array
    {
        return ['image'];
    }

    public function schema(): array
    {
        return [
            Section::make(__('scholarship::scholarship.settings.page'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("title.{$locale}")->label(__('scholarship::scholarship.settings.title'))->maxLength(150),
                    TextInput::make("text.{$locale}")->label(__('scholarship::scholarship.settings.text'))->maxLength(300),
                    RichEditor::make("intro.{$locale}")->label(__('scholarship::scholarship.settings.intro'))->fileAttachments(false),
                    RichEditor::make("outro.{$locale}")->label(__('scholarship::scholarship.settings.outro'))->fileAttachments(false),
                ]),
                MediaPicker::make('image')->label(__('scholarship::scholarship.settings.image'))->image()->storeIds(),
            ]),
            Section::make(__('scholarship::scholarship.settings.side_links'))->description(__('scholarship::scholarship.settings.side_links_help'))->compact()
                ->schema([LinkListField::make('side_links')->hiddenLabel()]),
            Section::make(__('scholarship::scholarship.settings.quick_links'))->compact()->schema([LinkListField::make('quick_links')->hiddenLabel()]),
        ];
    }
}
