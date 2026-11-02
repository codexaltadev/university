<?php

namespace Platform\Console\Commands;

use Illuminate\Console\Command;
use Platform\Media\MediaVisibility;

/**
 * Files follow their records' switches (MediaVisibility). Saving a record moves its files at once; this catches
 * what switched without a save — active_at reached, expired (--minutes, scheduled every five minutes) — and, without
 * --minutes, every file (after a migration, a restore, a failed move).
 */
class MediaVisibilityCommand extends Command
{
    protected $signature = 'uwms:media:visibility {--minutes= : only records whose dates passed in the last N minutes}';

    protected $description = 'Withhold the files of records that are off, release those of records that are on';

    public function handle(MediaVisibility $visibility): int
    {
        $moved = filled($this->option('minutes'))
            ? $visibility->refreshScheduled(now()->subMinutes((int) $this->option('minutes')))
            : $visibility->refreshAll();

        $this->components->info("{$moved['withheld']} files withheld, {$moved['released']} released.");

        return self::SUCCESS;
    }
}
