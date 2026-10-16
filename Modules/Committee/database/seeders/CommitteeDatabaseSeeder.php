<?php

namespace Modules\Committee\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Committee\Models\Committee;
use Platform\Taxonomy\Vocabularies;

/**
 * Required default data only, no demo content; must be safe to run twice.
 * FR-COM-01: the categories an Admin starts with (they may rename or add more).
 */
class CommitteeDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app(Vocabularies::class)->options(Committee::CATEGORY) !== []) {
            return;   // an Admin's list stays as it is
        }

        Vocabularies::ensureTerms(Committee::CATEGORY, [
            ['name' => ['en' => 'Statutory Bodies', 'bn' => 'সংবিধিবদ্ধ পর্ষদ'], 'slug' => 'statutory-bodies'],
            ['name' => ['en' => 'Academic', 'bn' => 'একাডেমিক'], 'slug' => 'academic'],
            ['name' => ['en' => 'Administrative', 'bn' => 'প্রশাসনিক'], 'slug' => 'administrative'],
        ]);
    }
}
