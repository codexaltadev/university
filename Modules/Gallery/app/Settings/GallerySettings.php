<?php

namespace Modules\Gallery\Settings;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Gallery: the gallery's own text — title, text and picture on top (DIU's are
 * the defaults). Read: settings('gallery.title'), settings('gallery.text'), media_url(settings('gallery.image')).
 */
class GallerySettings extends ModuleSettings
{
    public function group(): string
    {
        return 'gallery';
    }

    public function label(): string
    {
        return __('gallery::gallery.plural');
    }

    public function defaults(): array
    {
        return [
            'title' => ['en' => 'Gallery', 'bn' => 'গ্যালারি'],
            'text' => ['en' => 'Explore moments captured at Daffodil International University', 'bn' => 'ড্যাফোডিল ইন্টারন্যাশনাল ইউনিভার্সিটির মুহূর্তগুলো দেখুন'],
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
            Section::make(__('gallery::gallery.settings.page'))->description(__('gallery::gallery.settings.page_help'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("title.{$locale}")->label(__('gallery::gallery.settings.title'))->maxLength(150),
                    Textarea::make("text.{$locale}")->label(__('gallery::gallery.settings.text'))->rows(2)->maxLength(500),
                ]),
                MediaPicker::make('image')->label(__('gallery::gallery.settings.image'))->image()->storeIds(),
            ]),
        ];
    }
}
