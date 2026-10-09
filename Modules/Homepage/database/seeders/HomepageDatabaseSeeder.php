<?php

namespace Modules\Homepage\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Homepage\Models\Layout;

/**
 * The main homepage's default sections (FR-HOME-12): hero, statistics, programs,
 * news, events, notices, testimonials, partners. Module sections show once their
 * modules are active. Safe to run twice.
 */
class HomepageDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (Layout::query()->ownedBy('site:0')->exists()) {
            return;
        }

        $layout = Layout::query()->create(['is_active' => true]);

        foreach ([
            ['core.hero', null, ['slides' => []]],
            ['core.statistics', null, ['set' => 'default', 'display' => 'counter']],
            ['program.list', ['en' => 'Our Programs', 'bn' => 'আমাদের Program'], ['source_mode' => 'highlighted_first', 'count' => 8]],
            ['news.latest', ['en' => 'Latest News', 'bn' => 'সর্বশেষ সংবাদ'], ['source_mode' => 'highlighted_first', 'count' => 6]],
            ['event.upcoming', ['en' => 'Upcoming Events', 'bn' => 'আসন্ন অনুষ্ঠান'], ['source_mode' => 'latest', 'count' => 4]],
            ['notice.board', ['en' => 'Notice Board', 'bn' => 'নোটিশ বোর্ড'], ['source_mode' => 'latest', 'count' => 8]],
            ['testimonial.list', ['en' => 'What Our Students Say', 'bn' => 'শিক্ষার্থীদের কথা'], ['source_mode' => 'highlighted_first', 'count' => 6]],
            ['partner.list', ['en' => 'Our Partners', 'bn' => 'আমাদের অংশীদার'], ['source_mode' => 'highlighted_first', 'count' => 12]],
        ] as $i => [$type, $heading, $settings]) {
            $layout->sections()->create(['section_type' => $type, 'heading' => $heading, 'settings' => $settings, 'sort_order' => $i + 1]);
        }
    }
}
