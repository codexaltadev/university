<?php

namespace Modules\Leadership\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Leadership\Models\Position;
use Platform\Taxonomy\Vocabularies;

/**
 * Required default data only, no demo content; must be safe to run twice.
 * FR-LEAD-02: the groups an Admin starts with (they may rename or add more).
 */
class LeadershipDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app(Vocabularies::class)->options(Position::GROUP) !== []) {
            return;   // an Admin's list stays as it is
        }

        Vocabularies::ensureTerms(Position::GROUP, [
            ['name' => ['en' => 'Board of Trustees', 'bn' => 'ট্রাস্টি বোর্ড'], 'slug' => 'board-of-trustees'],
            ['name' => ['en' => 'Executive', 'bn' => 'নির্বাহী'], 'slug' => 'executive'],
            ['name' => ['en' => 'Academic Leadership', 'bn' => 'একাডেমিক নেতৃত্ব'], 'slug' => 'academic-leadership'],
        ]);
    }
}
