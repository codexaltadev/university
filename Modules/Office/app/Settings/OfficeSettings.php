<?php

namespace Modules\Office\Settings;

use Filament\Schemas\Components\Section;
use Platform\Filament\Components\LinkListField;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Offices: the Quick Links of an office page whose office has none of its own
 * (DIU's are the defaults). Read: link_list(settings('office.page_quick_links')).
 */
class OfficeSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'office';
    }

    public function label(): string
    {
        return __('office::office.plural');
    }

    public function defaults(): array
    {
        return [
            'page_quick_links' => [
                ['label' => ['en' => 'Notice', 'bn' => 'নোটিশ'], 'url' => '/notices'],
                ['label' => ['en' => 'Tuition Fees', 'bn' => 'টিউশন ফি'], 'url' => '/tuition-fees'],
                ['label' => ['en' => 'Waiver Calculator', 'bn' => 'ওয়েভার ক্যালকুলেটর'], 'url' => '/waiver-calculator'],
                ['label' => ['en' => 'Waiver & Scholarship', 'bn' => 'ওয়েভার ও স্কলারশিপ'], 'url' => '/scholarships'],
            ],
        ];
    }

    public function schema(): array
    {
        return [
            Section::make(__('office::office.settings.page_quick_links'))->description(__('office::office.settings.page_quick_links_help'))->compact()
                ->schema([LinkListField::make('page_quick_links')->hiddenLabel()]),
        ];
    }
}
