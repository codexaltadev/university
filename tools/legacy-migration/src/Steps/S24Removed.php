<?php

namespace Legacy\Migration\Steps;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Legacy\Migration\Support\IdMap;

/**
 * S24R Removed at DIU (Data Migration Plan S24): a record migrated from a legacy row that a newer
 * project_diu no longer has (or has deleted, deleted_at) goes to the trash — DIU's new transport
 * routes replace the old ones instead of showing both. Restored by Admin or by upsert when the row
 * comes back. Kept when another legacy row still leads to it (an office that was also a
 * "department", S10); a record without a trash is only turned off. Synthetic keys (admins@manual,
 * files) are not legacy tables and are left alone. Runs last.
 */
class S24Removed extends Step
{
    public static function key(): string
    {
        return 'S24R';
    }

    public static function title(): string
    {
        return 'Records whose DIU row is gone';
    }

    public static function requires(): array
    {
        return [];
    }

    protected function handle(): void
    {
        $legacy = Schema::connection('legacy');
        $gone = collect();
        $kept = [];   // "type:id" still reached from a present row

        foreach (DB::table(IdMap::TABLE)->distinct()->pluck('legacy_table') as $table) {
            $mapped = DB::table(IdMap::TABLE)->where('legacy_table', $table)->get(['legacy_id', 'new_type', 'new_id']);
            if (! $legacy->hasTable($table) || ! $legacy->hasColumn($table, 'id')) {
                $mapped->each(function ($m) use (&$kept): void {
                    $kept[$m->new_type.':'.$m->new_id] = true;
                });

                continue;
            }

            $present = $this->legacy()->table($table)->whereIn('id', $mapped->pluck('legacy_id'))
                ->when($legacy->hasColumn($table, 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                ->pluck('id')->map(fn ($id): int => (int) $id)->flip();
            foreach ($mapped as $m) {
                if ($present->has((int) $m->legacy_id)) {
                    $kept[$m->new_type.':'.$m->new_id] = true;
                } else {
                    $gone->push([$table, $m]);
                }
            }
        }

        foreach ($gone as [$table, $m]) {
            $class = Relation::getMorphedModel($m->new_type) ?? $m->new_type;
            if (isset($kept[$m->new_type.':'.$m->new_id]) || ! class_exists($class)
                || ! ($record = $class::query()->withoutGlobalScopes()->find($m->new_id))) {
                continue;
            }

            if (in_array(SoftDeletes::class, class_uses_recursive($record), true)) {
                if ($record->trashed()) {
                    continue;
                }
                $record->delete();
            } elseif ($record->isFillable('is_active') || array_key_exists('is_active', $record->getAttributes())) {
                if (! $record->is_active) {
                    continue;
                }
                $record->forceFill(['is_active' => false])->save();
            } else {
                $this->report->review("{$table} #{$m->legacy_id} is gone at DIU: {$m->new_type} {$m->new_id} has no trash or switch — remove it by hand if wanted.");

                continue;
            }

            $this->report->skipped($table, 'gone at DIU → trashed');
            $this->report->review("{$table} #{$m->legacy_id} is gone at DIU: {$m->new_type} {$m->new_id} \"".$this->name($record).'" moved to the trash (restore it in Admin if it should stay).');
        }
    }

    protected function name(Model $record): string
    {
        foreach (['title', 'name', 'label'] as $attribute) {
            $value = in_array($attribute, $record->translatable ?? [], true) ? $record->getTranslation($attribute, 'en', false) : $record->getAttribute($attribute);
            if (is_string($value) && filled($value)) {
                return $value;
            }
        }

        return '#'.$record->getKey();
    }
}
