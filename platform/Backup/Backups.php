<?php

namespace Platform\Backup;

use Closure;

/**
 * Database backup before a risky change (FR-BKP-03, BR-MOD-07): module and theme
 * install / update call beforeChange(). The Backup core module provides the
 * runner; without it (or when switched off, e.g. in tests) the step is skipped.
 *
 *   ['status' => 'success'|'failed'|'skipped', 'message' => …]
 */
class Backups
{
    protected ?Closure $runner = null;

    /** @param Closure(string $reason): array{status: string, message: ?string} $runner */
    public function useRunner(Closure $runner): void
    {
        $this->runner = $runner;
    }

    /** @return array{status: string, message: ?string} */
    public function beforeChange(string $reason): array
    {
        if ($this->runner === null) {
            return ['status' => 'skipped', 'message' => 'Backup module not active'];
        }

        if (! config('platform.backup.before_change', true)) {
            return ['status' => 'skipped', 'message' => 'Switched off (UWMS_BACKUP_BEFORE_CHANGE)'];
        }

        return ($this->runner)($reason);
    }
}
