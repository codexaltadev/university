<?php

namespace Platform\Console\Commands;

use Illuminate\Console\Command;
use Platform\Activity\Activity;

/**
 * Daily retention cleanup (BR-LOG-05/06, FR-LOG-06/07). Never shorter than 60 days,
 * whatever is configured; the cleanup itself is logged with its count.
 */
class CleanActivityLogCommand extends Command
{
    public const MINIMUM_DAYS = 60;

    protected $signature = 'uwms:activity:clean';

    protected $description = 'Delete activity log entries older than the retention period (minimum 60 days)';

    public static function retentionDays(): int
    {
        return max(self::MINIMUM_DAYS, (int) config('activitylog.clean_after_days', 90));
    }

    public function handle(): int
    {
        $days = self::retentionDays();
        $deleted = Activity::query()->where('created_at', '<', now()->subDays($days))->delete();

        activity('system')
            ->event('log_cleanup')
            ->withProperties(['deleted' => $deleted, 'retention_days' => $days])
            ->log('log_cleanup');

        $this->components->info("{$deleted} activity log entries older than {$days} days deleted.");

        return self::SUCCESS;
    }
}
