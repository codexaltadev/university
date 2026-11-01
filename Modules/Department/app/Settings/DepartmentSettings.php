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
 * and the Quick Links under "Browse by Faculty"; the heading of /coordination-officers. DIU's are the defaults.
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
            'coordinators_title' => ['en' => 'Coordination Officer Directory', 'bn' => 'কো-অর্ডিনেশন অফিসার তালিকা'],
            'coordinators_subtitle' => ['en' => 'Connect with faculty coordination officers and administrative staff across all departments', 'bn' => 'সব বিভাগের অনুষদ কো-অর্ডিনেশন অফিসার ও প্রশাসনিক কর্মকর্তাদের সাথে যোগাযোগ করুন'],
            'coordinators_image' => null,
            'quick_links' => [
                ['label' => ['en' => 'Tuition Fees', 'bn' => 'টিউশন ফি'], 'url' => '/tuition-fees'],
                ['label' => ['en' => 'Waiver & Scholarship', 'bn' => 'ওয়েভার ও স্কলারশিপ'], 'url' => '/scholarships'],
                ['label' => ['en' => 'Contact Page', 'bn' => 'যোগাযোগ'], 'url' => '/admission/contact'],
            ],
        ];
    }

    public function translatable(): array
    {
        return ['listing_title', 'listing_text', 'coordinators_title', 'coordinators_subtitle'];
    }

    public function media(): array
    {
        return ['listing_image', 'coordinators_image'];
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
            Section::make(__('department::department.settings.coordinators'))->description(__('department::department.settings.coordinators_help'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("coordinators_title.{$locale}")->label(__('department::department.settings.title'))->maxLength(150),
                    Textarea::make("coordinators_subtitle.{$locale}")->label(__('department::department.settings.text'))->rows(2)->maxLength(300),
                ]),
                MediaPicker::make('coordinators_image')->label(__('department::department.settings.image'))->image()->storeIds(),
            ]),
            Section::make(__('department::department.settings.quick_links'))->compact()
                ->schema([LinkListField::make('quick_links')->hiddenLabel()]),
        ];
    }
}
