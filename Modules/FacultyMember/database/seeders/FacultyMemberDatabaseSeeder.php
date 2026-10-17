<?php

namespace Modules\FacultyMember\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\FacultyMember\Models\FacultyMember;
use Platform\Taxonomy\Vocabularies;

/**
 * Required default data only, no demo content; must be safe to run twice.
 * FR-FM-05/06: the usual designation ladder, so the directory is in order from the first sync
 * (designations the faculty site uses beyond these are added by the sync, at the end).
 */
class FacultyMemberDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app(Vocabularies::class)->options(FacultyMember::DESIGNATION, activeOnly: false) !== []) {
            return;   // an Admin's ladder stays as it is
        }

        Vocabularies::ensureTerms(FacultyMember::DESIGNATION, [
            ['name' => ['en' => 'Professor', 'bn' => 'অধ্যাপক'], 'slug' => 'professor'],
            ['name' => ['en' => 'Associate Professor', 'bn' => 'সহযোগী অধ্যাপক'], 'slug' => 'associate-professor'],
            ['name' => ['en' => 'Assistant Professor', 'bn' => 'সহকারী অধ্যাপক'], 'slug' => 'assistant-professor'],
            ['name' => ['en' => 'Senior Lecturer', 'bn' => 'জ্যেষ্ঠ প্রভাষক'], 'slug' => 'senior-lecturer'],
            ['name' => ['en' => 'Lecturer', 'bn' => 'প্রভাষক'], 'slug' => 'lecturer'],
            ['name' => ['en' => 'Adjunct Faculty', 'bn' => 'খণ্ডকালীন শিক্ষক'], 'slug' => 'adjunct-faculty'],
        ]);
    }
}
