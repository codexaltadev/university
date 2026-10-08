<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Nwidart\Modules\Facades\Module;
use Platform\Modules\ModulePermissions;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Shield permissions of every active module (POC-1 mechanism)
        foreach (Module::allEnabled() as $module) {
            app(ModulePermissions::class)->sync($module->getName());
        }

        $this->call(SuperAdminSeeder::class);
    }
}
