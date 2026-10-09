<?php

namespace Modules\Program\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Taxonomy\Vocabularies;

/** Required default data only, no demo content; must be safe to run twice. */
class ProgramDatabaseSeeder extends Seeder
{
    /** FR-PROG-03: DIU's confirmed levels; admins may add more in Taxonomy. */
    public const LEVELS = [
        ['name' => ['en' => 'Undergraduate', 'bn' => 'স্নাতক'], 'slug' => 'undergraduate'],
        ['name' => ['en' => 'Graduate', 'bn' => 'স্নাতকোত্তর'], 'slug' => 'graduate'],
        ['name' => ['en' => 'Diploma', 'bn' => 'ডিপ্লোমা'], 'slug' => 'diploma'],
        ['name' => ['en' => 'Post Graduate Diploma', 'bn' => 'স্নাতকোত্তর ডিপ্লোমা'], 'slug' => 'post-graduate-diploma'],
    ];

    public function run(): void
    {
        Vocabularies::ensureTerms('program.level', self::LEVELS);
        app(Vocabularies::class)->find('program.tag');
    }
}
