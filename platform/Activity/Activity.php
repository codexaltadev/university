<?php

namespace Platform\Activity;

use LogicException;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * Activity log entry (DB-01 §6.1). Read-only once written (FR-LOG-09);
 * only the retention cleanup deletes rows.
 */
class Activity extends SpatieActivity
{
    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Activity log entries cannot be changed (FR-LOG-09).');
        });
    }
}
