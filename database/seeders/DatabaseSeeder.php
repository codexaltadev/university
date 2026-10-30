<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Bundled modules in core_modules, kernel and module permissions
        Artisan::call('uwms:modules:sync');

        $this->call(SuperAdminSeeder::class);

        // Core modules are synced, not installed, so their seeders run here; by name: the app does not depend on a module
        if (class_exists($seo = 'Modules\\Seo\\Database\\Seeders\\SeoDatabaseSeeder')) {
            $this->call($seo);   // default SEO description
        }
    }
}
