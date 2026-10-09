<?php

namespace Platform\Concerns;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Activity log for every business model (FR-GEN-90/91, BR-LOG-01/04):
 * created, updated, deleted, restored, force_deleted, activated, deactivated.
 * Only changed fields are stored; hidden and sensitive fields never are.
 */
trait LogsPlatformActivity
{
    use LogsActivity;

    /** Never logged, on any model. */
    protected static array $platformSensitiveAttributes = [
        'password', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes',
    ];

    /** Bookkeeping columns that alone never make a log entry. */
    protected static array $platformBookkeepingAttributes = [
        'updated_at', 'updated_by', 'created_by', 'created_at', 'deleted_by', 'active_key',
    ];

    public static function bootLogsPlatformActivity(): void
    {
        // Columns filled by database defaults (e.g. users.locale) are not on the
        // in-memory model after insert; load them so the next update does not
        // report them as changed.
        static::created(function (Model $model): void {
            $row = $model->newQueryWithoutScopes()->toBase()
                ->where($model->getKeyName(), $model->getKey())
                ->first();

            $missing = array_diff_key((array) $row, $model->getAttributes());

            if ($missing !== []) {
                $model->setRawAttributes(array_merge($model->getAttributes(), $missing));
            }
        });

        // Spatie logs force deletes as "deleted"; record them under their own name.
        if (method_exists(static::class, 'forceDeleted')) {
            static::forceDeleting(fn (Model $model) => $model->platformForceDeleting = true);
        }
    }

    public bool $platformForceDeleting = false;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('model')
            ->logAll()
            ->logExcept(array_merge(
                static::$platformSensitiveAttributes,
                $this->getHidden(),
                ['updated_at', 'created_at', 'active_key'],
            ))
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(static::$platformBookkeepingAttributes)
            ->dontLogEmptyChanges();
    }

    /** Called by spatie just before the activity is saved. */
    public function beforeActivityLogged(Model $activity, string $event): void
    {
        if ($event === 'deleted' && $this->platformForceDeleting) {
            $activity->event = 'force_deleted';
            $activity->description = 'force_deleted';
        }

        if ($event === 'updated') {
            $changed = array_keys($activity->attribute_changes['attributes'] ?? []);

            if ($changed === ['is_active']) {
                $activity->event = $this->is_active ? 'activated' : 'deactivated';
                $activity->description = $activity->event;
            }
        }
    }
}
