<?php

namespace Legacy\Migration\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Legacy\Migration\Steps\S02Vocabularies;
use Legacy\Migration\Steps\S04FacultiesDepartments;
use Legacy\Migration\Steps\S06Programs;
use Legacy\Migration\Steps\S07Fees;
use Legacy\Migration\Steps\S07Scholarships;
use Legacy\Migration\Steps\S08Admission;
use Legacy\Migration\Steps\S09Calendars;
use Legacy\Migration\Steps\S10Offices;
use Legacy\Migration\Steps\S11Administration;
use Legacy\Migration\Steps\S11Alumni;
use Legacy\Migration\Steps\S13Committees;
use Legacy\Migration\Steps\S15DepartmentSites;
use Legacy\Migration\Steps\S17Faqs;
use Legacy\Migration\Steps\Step;
use Platform\Modules\ModuleRegistry;
use Spatie\Activitylog\Support\ActivityLogStatus;
use Throwable;

/**
 * php artisan legacy:migrate --all | --step=S04 --step=S06 (Data Migration Plan §4, §5).
 * Each step runs in a transaction, can be run again (M-3) and writes its report to
 * tools/legacy-migration/reports/ (M-7). The activity log is off meanwhile (M-5).
 */
class MigrateCommand extends Command
{
    protected $signature = 'legacy:migrate {--step=* : S02, S04, S06 …} {--all : every step in order} {--no-report : do not write report files}';

    protected $description = 'Migrate DIU data from the legacy database (project_diu)';

    /** In the order of the plan (§5). */
    public const STEPS = [S02Vocabularies::class, S04FacultiesDepartments::class, S06Programs::class, S07Fees::class, S07Scholarships::class, S08Admission::class, S09Calendars::class, S10Offices::class, S11Administration::class, S11Alumni::class, S13Committees::class, S15DepartmentSites::class, S17Faqs::class];

    public function handle(): int
    {
        $wanted = array_map('strtoupper', (array) $this->option('step'));
        $steps = collect(self::STEPS)->filter(fn (string $step): bool => $this->option('all') || in_array($step::key(), $wanted, true))->values();

        if ($steps->isEmpty()) {
            $this->error('Choose --all or --step=… ('.collect(self::STEPS)->map(fn (string $s): string => $s::key())->implode(', ').').');

            return self::FAILURE;
        }

        try {
            DB::connection('legacy')->getPdo();
        } catch (Throwable $e) {
            $this->error('The legacy database is not reachable (LEGACY_DB_* in .env): '.$e->getMessage());

            return self::FAILURE;
        }

        $modules = app(ModuleRegistry::class);
        $log = app(ActivityLogStatus::class);
        $log->disable();

        try {
            foreach ($steps as $class) {
                /** @var class-string<Step> $class */
                $missing = array_filter($class::requires(), fn (string $slug): bool => ! $modules->isActive($slug));

                if ($missing !== []) {
                    $this->warn("{$class::key()} skipped: module(s) ".implode(', ', $missing).' not active.');

                    continue;
                }

                $this->info("{$class::key()} — {$class::title()}");
                $report = DB::transaction(fn () => app($class)->run());

                foreach ($report->tables as $table => $row) {
                    $skipped = array_sum($row['skipped']);
                    $this->line(sprintf('  %-34s source %4d · created %4d · updated %4d · skipped %4d', $table, $row['source'], $row['created'], $row['updated'], $skipped));
                }

                if ($report->review !== []) {
                    $this->line('  manual review: '.count($report->review).' item(s)');
                }

                if ($this->option('no-report')) {
                    continue;
                }

                $path = base_path('tools/legacy-migration/reports/'.$class::key().'-'.now()->format('Ymd-His').'.md');
                File::ensureDirectoryExists(dirname($path));
                File::put($path, $report->toMarkdown());
                $this->line('  report: '.str_replace(base_path().'/', '', $path));
            }
        } finally {
            $log->enable();
        }

        return self::SUCCESS;
    }
}
