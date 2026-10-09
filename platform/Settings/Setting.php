<?php

namespace Platform\Settings;

use Illuminate\Database\Eloquent\Model;
use Platform\Media\MediaUsages;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * One website setting (core_settings, DB-01 §7.1). System table: no soft delete,
 * no is_active (BRL-SET-01). Read and write through Platform\Settings\Settings.
 * Every change is in the activity log as "{group}.{key}: old → new"; encrypted values are masked.
 */
class Setting extends Model
{
    use LogsActivity;

    public static string $titleAttribute = 'key';

    protected $table = 'core_settings';

    protected $fillable = ['group', 'key', 'value', 'is_translatable', 'is_encrypted', 'is_public', 'updated_by'];

    protected function casts(): array
    {
        return ['value' => 'json', 'is_translatable' => 'boolean', 'is_encrypted' => 'boolean', 'is_public' => 'boolean'];
    }

    protected static function booted(): void
    {
        // A removed setting no longer uses its file
        static::deleted(fn (Setting $setting) => app(MediaUsages::class)->clear($setting));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('model')->logOnly(['value'])->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function beforeActivityLogged(Model $activity, string $event): void
    {
        $name = "{$this->group}.{$this->key}";
        $mask = fn (mixed $value): mixed => $this->is_encrypted && $value !== null ? '••••••' : $value;
        $changes = $activity->attribute_changes;

        $activity->attribute_changes = collect(['attributes', 'old'])
            ->filter(fn (string $side): bool => isset($changes[$side]))
            ->mapWithKeys(fn (string $side): array => [$side => [$name => $mask($changes[$side]['value'] ?? null)]])
            ->all();
    }
}
