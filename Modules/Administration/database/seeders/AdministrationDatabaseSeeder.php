<?php

namespace Modules\Administration\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Administration\Models\AdministrationCategory;

/**
 * Required default data only, no demo content; must be safe to run twice.
 * FR-ADMN-02: a three-rung ladder to start with when there is none — an Admin renames,
 * splits and reorders it (Director, Deputy Registrar … Driver).
 */
class AdministrationDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (AdministrationCategory::query()->withTrashed()->exists()) {
            return;
        }

        foreach ([
            ['name' => ['en' => 'Management', 'bn' => 'ব্যবস্থাপনা'], 'group' => 'management', 'is_public' => true],
            ['name' => ['en' => 'Officer', 'bn' => 'কর্মকর্তা'], 'group' => 'officer', 'is_public' => true],
            ['name' => ['en' => 'Support Staff', 'bn' => 'সহায়ক কর্মী'], 'group' => 'support', 'is_public' => false],
        ] as $i => $category) {
            AdministrationCategory::query()->create($category + ['sort_order' => $i + 1, 'is_active' => true]);
        }
    }
}
