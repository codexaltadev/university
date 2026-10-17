<?php

namespace Modules\Transport\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Transport\Models\ServiceType;

/** Required default data only, no demo content; must be safe to run twice. FR-TRN-03: one default service. */
class TransportDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (ServiceType::query()->withTrashed()->exists()) {
            return;   // an Admin's list stays as it is
        }

        ServiceType::query()->create(['name' => ['en' => 'Regular Service', 'bn' => 'নিয়মিত সেবা'], 'code' => 'regular_service', 'is_default' => true, 'is_active' => true]);
    }
}
