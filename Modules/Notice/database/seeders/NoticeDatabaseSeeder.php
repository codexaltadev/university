<?php

namespace Modules\Notice\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Notice\Models\Notice;
use Platform\Taxonomy\Vocabularies;

/**
 * Required default data only, no demo content; must be safe to run twice.
 * The notice categories an Admin starts with (they may rename or add more).
 */
class NoticeDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app(Vocabularies::class)->options(Notice::CATEGORY) !== []) {
            return;   // an Admin's list stays as it is
        }

        Vocabularies::ensureTerms(Notice::CATEGORY, [
            ['name' => ['en' => 'Academic', 'bn' => 'একাডেমিক'], 'slug' => 'academic'],
            ['name' => ['en' => 'Examination', 'bn' => 'পরীক্ষা'], 'slug' => 'examination'],
            ['name' => ['en' => 'Admission', 'bn' => 'ভর্তি'], 'slug' => 'admission'],
            ['name' => ['en' => 'Holiday', 'bn' => 'ছুটি'], 'slug' => 'holiday'],
            ['name' => ['en' => 'Tender', 'bn' => 'দরপত্র'], 'slug' => 'tender'],
        ]);
    }
}
