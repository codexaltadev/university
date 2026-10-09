<?php

namespace Modules\Taxonomy\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Taxonomy\Vocabularies;

/** Rows for every registered vocabulary (shared Tags included). Safe to run twice. */
class TaxonomyDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(Vocabularies::class)->sync();
    }
}
