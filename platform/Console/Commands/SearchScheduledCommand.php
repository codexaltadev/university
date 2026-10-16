<?php

namespace Platform\Console\Commands;

use Illuminate\Console\Command;
use Platform\Concerns\HasExpiry;
use Platform\Scope\UserScopeFilter;
use Platform\Search\SearchIndex;
use Platform\Support\PublishDate;

/**
 * Scheduled content becomes visible (or expires) without being saved; this picks
 * up what crossed its publish or expiry time in the last minutes (SAD Part B §10,
 * FR-GEN-11, FR-SRCH-09). Runs every minute.
 */
class SearchScheduledCommand extends Command
{
    protected $signature = 'uwms:search:scheduled {--minutes=5}';

    protected $description = 'Index content whose publish date or expiry just passed';

    public function handle(SearchIndex $index): int
    {
        $since = now()->subMinutes((int) $this->option('minutes'));
        $count = 0;

        foreach (array_keys($index->registered()) as $class) {
            $traits = class_uses_recursive($class);
            $columns = array_filter([
                PublishDate::column($class),   // published_at or active_at
                in_array(HasExpiry::class, $traits, true) ? 'expires_at' : null,
            ]);

            foreach ($columns as $column) {
                $class::query()->withoutGlobalScope(UserScopeFilter::class)
                    ->whereBetween($column, [$since, now()])
                    ->lazyById(200)
                    ->each(function ($record) use ($index, &$count): void {
                        $index->refresh($record->getMorphClass(), $record->getKey());
                        $count++;
                    });
            }
        }

        $this->components->info("Refreshed {$count} records.");

        return self::SUCCESS;
    }
}
