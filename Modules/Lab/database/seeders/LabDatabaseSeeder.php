<?php

namespace Modules\Lab\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Lab\Models\Lab;
use Platform\Taxonomy\Vocabularies;

/**
 * Required default data only, no demo content; must be safe to run twice.
 * FR-LAB-01: the lab types an Admin starts with (they may rename or add more).
 */
class LabDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app(Vocabularies::class)->options(Lab::TYPE) !== []) {
            return;   // an Admin's list stays as it is
        }

        Vocabularies::ensureTerms(Lab::TYPE, [
            ['name' => ['en' => 'Teaching Lab', 'bn' => 'টিচিং ল্যাব'], 'slug' => 'teaching-lab'],
            ['name' => ['en' => 'Research Lab', 'bn' => 'রিসার্চ ল্যাব'], 'slug' => 'research-lab'],
            ['name' => ['en' => 'Research Center', 'bn' => 'রিসার্চ সেন্টার'], 'slug' => 'research-center'],
            ['name' => ['en' => 'Innovation Hub', 'bn' => 'ইনোভেশন হাব'], 'slug' => 'innovation-hub'],
        ]);
    }
}
