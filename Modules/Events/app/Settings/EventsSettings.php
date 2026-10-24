<?php

namespace Modules\Events\Settings;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Events: the events listing's own text — title, text and picture on top, the
 * line under "Upcoming Events" / "Archive Events" — and the picture on top of an event's page.
 * Read: settings('events.listing_title'), media_url(settings('events.listing_image')), settings('events.intro').
 */
class EventsSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'events';
    }

    public function label(): string
    {
        return __('events::events.plural');
    }

    public function defaults(): array
    {
        return [
            'listing_title' => ['en' => 'Explore Our Events', 'bn' => 'আমাদের ইভেন্ট'],
            'listing_text' => [
                'en' => 'Seminars, workshops, competitions and celebrations across DIU\'s faculties, departments and offices.',
                'bn' => 'ফ্যাকাল্টি, ডিপার্টমেন্ট ও অফিসের সেমিনার, ওয়ার্কশপ, প্রতিযোগিতা ও উৎসব।',
            ],
            'listing_image' => null,
            'intro' => [
                'en' => 'From hands-on workshops to breakthrough announcements—never miss what\'s shaping tomorrow\'s innovations at DIU.',
                'bn' => 'হাতে-কলমে ওয়ার্কশপ থেকে বড় ঘোষণা — DIU-তে যা ঘটছে তার কিছুই যেন বাদ না পড়ে।',
            ],
            'event_image' => null,
        ];
    }

    public function translatable(): array
    {
        return ['listing_title', 'listing_text', 'intro'];
    }

    public function media(): array
    {
        return ['listing_image', 'event_image'];
    }

    public function schema(): array
    {
        return [
            Section::make(__('events::events.settings.listing'))->description(__('events::events.settings.listing_help'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("listing_title.{$locale}")->label(__('events::events.settings.title'))->maxLength(150),
                    Textarea::make("listing_text.{$locale}")->label(__('events::events.settings.text'))->rows(3)->maxLength(1000),
                    Textarea::make("intro.{$locale}")->label(__('events::events.settings.intro'))->rows(2)->maxLength(500),
                ]),
                MediaPicker::make('listing_image')->label(__('events::events.settings.image'))->image()->storeIds(),
                MediaPicker::make('event_image')->label(__('events::events.settings.event_image'))->helperText(__('events::events.settings.event_image_help'))->image()->storeIds(),
            ]),
        ];
    }
}
