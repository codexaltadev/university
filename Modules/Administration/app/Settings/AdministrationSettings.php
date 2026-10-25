<?php

namespace Modules\Administration\Settings;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\LinkListField;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Administration (FR-ADMN-05): profile pages on or off, the page's title, picture
 * and text above /administration, and the Quick Links under its sections (DIU's are the defaults).
 * Read: settings('administration.profile_pages'), settings('administration.title'), settings('administration.intro'),
 * media_url(settings('administration.image')), link_list(settings('administration.quick_links')).
 */
class AdministrationSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'administration';
    }

    public function label(): string
    {
        return __('administration::administration.plural');
    }

    public function defaults(): array
    {
        return [
            'profile_pages' => true,
            'title' => ['en' => 'Administration', 'bn' => 'প্রশাসন'],
            'image' => null,
            'intro' => null,
            'quick_links' => [
                ['label' => ['en' => 'Notice', 'bn' => 'নোটিশ'], 'url' => '/notices'],
                ['label' => ['en' => 'Tuition Fees', 'bn' => 'টিউশন ফি'], 'url' => '/tuition-fees'],
                ['label' => ['en' => 'Waiver Calculator', 'bn' => 'ওয়েভার ক্যালকুলেটর'], 'url' => '/waiver-calculator'],
                ['label' => ['en' => 'Waiver & Scholarship', 'bn' => 'ওয়েভার ও স্কলারশিপ'], 'url' => '/scholarships'],
            ],
        ];
    }

    public function translatable(): array
    {
        return ['title', 'intro'];
    }

    public function media(): array
    {
        return ['image'];
    }

    public function schema(): array
    {
        return [
            Toggle::make('profile_pages')->label(__('administration::administration.settings.profile_pages'))
                ->helperText(__('administration::administration.settings.profile_pages_help')),
            TranslatableTabs::make()->schema(fn (string $locale): array => [
                TextInput::make("title.{$locale}")->label(__('administration::administration.settings.title'))->maxLength(150),
                RichEditor::make("intro.{$locale}")->label(__('administration::administration.settings.intro'))->fileAttachments(false),
            ]),
            MediaPicker::make('image')->label(__('administration::administration.settings.image'))->image()->storeIds(),
            Section::make(__('administration::administration.settings.quick_links'))->description(__('administration::administration.settings.quick_links_help'))->compact()
                ->schema([LinkListField::make('quick_links')->hiddenLabel()]),
        ];
    }
}
