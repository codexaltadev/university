<?php

namespace Modules\ThemeManager\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Theme\Themes;

/** Records the bundled themes; the default one is active (BRL-THEME-01). Safe to run twice. */
class ThemeManagerDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(Themes::class)->sync();
    }
}
