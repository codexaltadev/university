<?php

namespace Modules\{{Name}}\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class {{Name}}DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotent default data
        DB::table('{{table}}')->updateOrInsert(['name' => 'Default'], ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    }
}
