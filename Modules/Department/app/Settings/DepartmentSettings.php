<?php

namespace Modules\Department\Settings;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\LinkListField;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Departments: the /departments page's own text — title, text and picture on top —
 * and the Quick Links under "Browse by Faculty". DIU's are the defaults.
 * Read with settings('department.…'), media_url(), link_list().
 */
class DepartmentSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'department';
    }

    public function label(): string
    {
        return __('department::department.plural');
    }

    public function defaults(): array
    {
        return [
            'listing_title' => null,
            'listing_text' => null,
            'listing_image' => null,
            'quick_links' => [
                ['label' => ['en' => 'Tuition Fees', 'bn' => 'টিউশন ফি'], 'url' => '/tuition-fees'],
                ['label' => ['en' => 'Waiver & Scholarship', 'bn' => 'ওয়েভার ও স্কলারশিপ'], 'url' => '/scholarships'],
                ['label' => ['en' => 'Contact Page', 'bn' => 'যোগাযোগ'], 'url' => '/admission/contact'],
            ],
        ];
    }

    public function translatable(): array
    {
        return ['listing_title', 'listing_text'];
    }

    public function media(): array
    {
        return ['listing_image'];
    }

    public function schema(): array
    {
        return [
            Section::make(__('department::department.settings.listing'))->description(__('department::department.settings.listing_help'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("listing_title.{$locale}")->label(__('department::department.settings.title'))->maxLength(150),
                    Textarea::make("listing_text.{$locale}")->label(__('department::department.settings.text'))->rows(3)->maxLength(1000),
                ]),
                MediaPicker::make('listing_image')->label(__('department::department.settings.image'))->image()->storeIds(),
            ]),
            Section::make(__('department::department.settings.quick_links'))->compact()
                ->schema([LinkListField::make('quick_links')->hiddenLabel()]),
        ];
    }
}
