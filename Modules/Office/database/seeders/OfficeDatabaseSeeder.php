<?php

namespace Modules\Office\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Office\Models\Office;
use Platform\Taxonomy\Vocabularies;

/**
 * Required default data only, no demo content; must be safe to run twice.
 * FR-OFF-01: the office types an Admin starts with (they may rename or add more).
 */
class OfficeDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app(Vocabularies::class)->options(Office::TYPE) !== []) {
            return;   // an Admin's list stays as it is
        }

        Vocabularies::ensureTerms(Office::TYPE, [
            ['name' => ['en' => 'Administration', 'bn' => 'প্রশাসন'], 'slug' => 'administration'],
            ['name' => ['en' => 'Academic Support', 'bn' => 'Academic সহায়তা'], 'slug' => 'academic-support'],
            ['name' => ['en' => 'Student Services', 'bn' => 'শিক্ষার্থী সেবা'], 'slug' => 'student-services'],
        ]);
    }
}
