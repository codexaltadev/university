<?php

namespace Legacy\Migration\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Legacy\Migration\Support\Seed;
use Throwable;

/**
 * php artisan legacy:export-seed — writes tools/legacy-migration/seed/ from project_diu (read only),
 * for `legacy:migrate --all --from-seed` where the legacy database is not at hand.
 */
class ExportSeedCommand extends Command
{
    protected $signature = 'legacy:export-seed {--path= : another folder than tools/legacy-migration/seed}';

    protected $description = 'Write the seed files (the legacy tables the migration reads) from the legacy database';

    public function handle(Seed $seed): int
    {
        try {
            DB::connection('legacy')->getPdo();
        } catch (Throwable $e) {
            $this->error('The legacy database is not reachable (LEGACY_DB_* in .env): '.$e->getMessage());

            return self::FAILURE;
        }

        $dir = $this->option('path') ?: Seed::path();
        $written = $seed->export(DB::connection('legacy'), $dir);

        foreach ($written as $table => $rows) {
            $note = in_array($table, Seed::NO_ROWS, true) ? '  (personal data: structure only)' : (isset(Seed::BLANK[$table]) ? '  (private columns empty)' : '');
            $this->line(sprintf('  %-38s %6d%s', $table, $rows, $note));
        }
        $this->info(count($written).' tables, '.array_sum($written).' rows → '.str_replace(base_path().'/', '', $dir));

        return self::SUCCESS;
    }
}
