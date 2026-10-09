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
    }
}
