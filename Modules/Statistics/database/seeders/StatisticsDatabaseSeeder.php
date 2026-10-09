<?php

namespace Modules\Statistics\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Statistics\StatItem;
use Platform\Statistics\StatSet;

/**
 * The main site's default counters (SRS C-20): Students (manual), Programs, Faculty
 * Members and Alumni (automatic — shown once their modules are active). Entity sets
 * are seeded by the modules that own the entities. Safe to run twice.
 */
class StatisticsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (StatSet::query()->ownedBy('site:0')->where('key', 'default')->exists()) {
            return;
        }

        $set = StatSet::query()->create(['key' => 'default', 'name' => ['en' => 'Homepage counters', 'bn' => 'Homepage-এর সংখ্যা']]);

        foreach ([
            ['label' => ['en' => 'Students', 'bn' => 'শিক্ষার্থী'], 'source' => 'manual', 'rounding' => 'thousand'],
            ['label' => ['en' => 'Programs', 'bn' => 'Program'], 'source' => 'metric', 'metric_key' => 'program.count'],
            ['label' => ['en' => 'Faculty Members', 'bn' => 'শিক্ষক'], 'source' => 'metric', 'metric_key' => 'faculty-member.count'],
            ['label' => ['en' => 'Alumni', 'bn' => 'প্রাক্তন শিক্ষার্থী'], 'source' => 'metric', 'metric_key' => 'alumni.count', 'rounding' => 'thousand'],
        ] as $i => $item) {
            StatItem::query()->create([...$item, 'stat_set_id' => $set->id, 'sort_order' => $i + 1]);
        }
    }
}
