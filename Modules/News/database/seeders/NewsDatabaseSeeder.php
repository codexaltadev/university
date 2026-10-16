<?php

namespace Modules\News\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\News\Models\Post;
use Platform\Taxonomy\Vocabularies;

/**
 * Required default data only, no demo content; must be safe to run twice.
 * The news categories an Admin starts with (they may rename or add more).
 */
class NewsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app(Vocabularies::class)->options(Post::CATEGORY) !== []) {
            return;   // an Admin's list stays as it is
        }

        Vocabularies::ensureTerms(Post::CATEGORY, [
            ['name' => ['en' => 'Campus News', 'bn' => 'ক্যাম্পাস সংবাদ'], 'slug' => 'campus-news'],
            ['name' => ['en' => 'Academic', 'bn' => 'একাডেমিক'], 'slug' => 'academic'],
            ['name' => ['en' => 'Research & Innovation', 'bn' => 'গবেষণা ও উদ্ভাবন'], 'slug' => 'research-innovation'],
            ['name' => ['en' => 'Achievement', 'bn' => 'অর্জন'], 'slug' => 'achievement'],
            ['name' => ['en' => 'Student Life', 'bn' => 'শিক্ষার্থী জীবন'], 'slug' => 'student-life'],
        ]);
    }
}
