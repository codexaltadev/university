<?php

namespace Modules\Events\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Events\Models\Event;
use Platform\Taxonomy\Vocabularies;

/**
 * Required default data only, no demo content; must be safe to run twice.
 * The event categories an Admin starts with (they may rename or add more).
 */
class EventsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app(Vocabularies::class)->options(Event::CATEGORY) !== []) {
            return;   // an Admin's list stays as it is
        }

        Vocabularies::ensureTerms(Event::CATEGORY, [
            ['name' => ['en' => 'Seminar & Workshop', 'bn' => 'সেমিনার ও কর্মশালা'], 'slug' => 'seminar-workshop'],
            ['name' => ['en' => 'Conference', 'bn' => 'সম্মেলন'], 'slug' => 'conference'],
            ['name' => ['en' => 'Cultural', 'bn' => 'সাংস্কৃতিক'], 'slug' => 'cultural'],
            ['name' => ['en' => 'Sports', 'bn' => 'খেলাধুলা'], 'slug' => 'sports'],
            ['name' => ['en' => 'Convocation', 'bn' => 'সমাবর্তন'], 'slug' => 'convocation'],
        ]);
    }
}
