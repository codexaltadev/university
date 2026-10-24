<?php

namespace Modules\News\Settings;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › News: posts per page and whether the author shows, and the news pages' own
 * text — the "Latest" heading and its text, the "All News" title, the call under a post (DIU News'
 * are the defaults). Layouts are chosen in Appearance › Layouts (news.listing, news.detail).
 * Read: settings('news.per_page'), settings('news.show_author'), settings('news.latest_title') …
 */
class NewsSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'news';
    }

    public function label(): string
    {
        return __('news::news.plural');
    }

    public function defaults(): array
    {
        return [
            'per_page' => 12,
            'show_author' => true,
            'latest_title' => ['en' => 'Latest', 'bn' => 'সর্বশেষ'],
            'latest_text' => [
                'en' => 'Stay updated with DIU’s most recent achievements, innovations, and milestones. From academic breakthroughs to student success stories, get all the latest updates that define our journey towards excellence!',
                'bn' => 'DIU-র সর্বশেষ অর্জন, উদ্ভাবন ও মাইলফলকের খবর — একাডেমিক সাফল্য থেকে শিক্ষার্থীদের গল্প, সব এক জায়গায়।',
            ],
            'all_title' => ['en' => 'All News', 'bn' => 'সব সংবাদ'],
            'explore_title' => ['en' => 'Explore the Inspiring Stories and Events of DIU Campus', 'bn' => 'DIU ক্যাম্পাসের অনুপ্রেরণার গল্প ও আয়োজন'],
            'explore_button' => ['en' => 'More News', 'bn' => 'আরও সংবাদ'],
        ];
    }

    public function translatable(): array
    {
        return ['latest_title', 'latest_text', 'all_title', 'explore_title', 'explore_button'];
    }

    public function schema(): array
    {
        return [
            TextInput::make('per_page')->label(__('news::news.settings.per_page'))->integer()->minValue(4)->maxValue(60)->required(),
            Toggle::make('show_author')->label(__('news::news.settings.show_author')),
            Section::make(__('news::news.settings.page'))->description(__('news::news.settings.page_help'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("latest_title.{$locale}")->label(__('news::news.settings.latest_title'))->maxLength(100),
                    Textarea::make("latest_text.{$locale}")->label(__('news::news.settings.latest_text'))->rows(3)->maxLength(600),
                    TextInput::make("all_title.{$locale}")->label(__('news::news.settings.all_title'))->maxLength(100),
                    TextInput::make("explore_title.{$locale}")->label(__('news::news.settings.explore_title'))->maxLength(150),
                    TextInput::make("explore_button.{$locale}")->label(__('news::news.settings.explore_button'))->maxLength(50),
                ]),
            ]),
        ];
    }
}
