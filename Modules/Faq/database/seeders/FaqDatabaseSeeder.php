<?php

namespace Modules\Faq\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Taxonomy\Vocabularies;

/** Required default data only; safe to run twice (Module Guide §13). */
class FaqDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Vocabularies::ensureTerms('faq.category', [
            ['slug' => 'general', 'name' => ['en' => 'General', 'bn' => 'সাধারণ']],
        ]);
    }
}
