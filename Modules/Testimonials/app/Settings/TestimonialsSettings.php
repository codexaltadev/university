<?php

namespace Modules\Testimonials\Settings;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Testimonials: the page's own text — title, text and picture on top (DIU's are
 * the defaults). Read: settings('testimonials.title'), settings('testimonials.text'), media_url(settings('testimonials.image')).
 */
class TestimonialsSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'testimonials';
    }

    public function label(): string
    {
        return __('testimonials::testimonials.plural');
    }

    public function defaults(): array
    {
        return [
            'title' => ['en' => 'Testimonials', 'bn' => 'অভিজ্ঞতা'],
            'text' => [
                'en' => 'Hear from students, faculty, and visitors about their experiences at Daffodil International University',
                'bn' => 'ড্যাফোডিল ইন্টারন্যাশনাল ইউনিভার্সিটিতে শিক্ষার্থী, শিক্ষক ও অতিথিদের অভিজ্ঞতা শুনুন',
            ],
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
            Section::make(__('testimonials::testimonials.settings.page'))->description(__('testimonials::testimonials.settings.page_help'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("title.{$locale}")->label(__('testimonials::testimonials.settings.title'))->maxLength(150),
                    Textarea::make("text.{$locale}")->label(__('testimonials::testimonials.settings.text'))->rows(2)->maxLength(500),
                ]),
                MediaPicker::make('image')->label(__('testimonials::testimonials.settings.image'))->image()->storeIds(),
            ]),
        ];
    }
}
