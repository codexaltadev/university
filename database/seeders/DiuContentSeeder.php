<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

/**
 * DIU's content from the seed files (tools/legacy-migration/seed): every migration step,
 * reading a copy of project_diu's tables instead of the legacy database.
 *
 *   php artisan db:seed --class=DiuContentSeeder
 *
 * Not part of DatabaseSeeder: another university's installation does not use it.
 * Run again any time — records are updated, not duplicated (migration_id_map).
 */
class DiuContentSeeder extends Seeder
{
    public function run(): void
    {
        $exit = Artisan::call('legacy:migrate', ['--all' => true, '--from-seed' => true], $this->command?->getOutput());

        if ($exit !== 0) {
            throw new RuntimeException('legacy:migrate --from-seed failed.');
        }
    }
}
