<?php

namespace Modules\Facilities\Settings;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Facilities: the /facilities page's title, line and picture on top.
 * Read: settings('facilities.title'), settings('facilities.text'), media_url(settings('facilities.image')).
 */
class FacilitiesSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'facilities';
    }

    public function label(): string
    {
        return __('facilities::facilities.plural');
    }

    public function defaults(): array
    {
        return [
            'title' => ['en' => 'Campus Facilities', 'bn' => 'ক্যাম্পাসের সুবিধা'],
            'text' => ['en' => 'Everything for learning and living at Daffodil Smart City — from libraries and labs to transport, housing and sports', 'bn' => 'পড়াশোনা ও জীবনযাপনের সব সুবিধা — লাইব্রেরি, ল্যাব, পরিবহন, আবাসন ও খেলাধুলা'],
            'image' => null,
        ];
    }

    public function translatable(): array
    {
        return ['title', 'text'];
    }

    public function media(): array
    {
        return ['image'];
    }

    public function schema(): array
    {
        return [
            Section::make(__('facilities::facilities.settings.page'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("title.{$locale}")->label(__('facilities::facilities.settings.title'))->maxLength(150),
                    Textarea::make("text.{$locale}")->label(__('facilities::facilities.settings.text'))->rows(2)->maxLength(300),
                ]),
                MediaPicker::make('image')->label(__('facilities::facilities.settings.image'))->image()->storeIds(),
            ]),
        ];
    }
}
