<?php

namespace Legacy\Migration\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** migration_id_map (DB-01 §13, rule M-3): legacy row ↔ new record. */
class IdMap
{
    public const TABLE = 'migration_id_map';

    /** New id of a legacy row, or null when it was not migrated. */
    public function find(string $legacyTable, int|string|null $legacyId): ?int
    {
        if ($legacyId === null || $legacyId === '' || (int) $legacyId === 0) {
            return null;
        }

        $id = DB::table(self::TABLE)->where('legacy_table', $legacyTable)->where('legacy_id', (int) $legacyId)->value('new_id');

        return $id === null ? null : (int) $id;
    }

    public function forget(string $legacyTable, int|string $legacyId): void
    {
        DB::table(self::TABLE)->where('legacy_table', $legacyTable)->where('legacy_id', (int) $legacyId)->delete();
    }

    /** @return list<int> new ids of one type already mapped from this legacy table */
    public function ownedIds(string $legacyTable, string $newType): array
    {
        return DB::table(self::TABLE)->where('legacy_table', $legacyTable)->where('new_type', $newType)->pluck('new_id')->map(fn ($id): int => (int) $id)->all();
    }

    /** Whether a row of this legacy table is already mapped to the record. */
    public function owns(string $legacyTable, Model $record): bool
    {
        return DB::table(self::TABLE)->where('legacy_table', $legacyTable)
            ->where('new_type', $record->getMorphClass())->where('new_id', $record->getKey())->exists();
    }

    public function remember(string $legacyTable, int|string $legacyId, Model $record): void
    {
        DB::table(self::TABLE)->updateOrInsert(
            ['legacy_table' => $legacyTable, 'legacy_id' => (int) $legacyId],
            ['new_type' => $record->getMorphClass(), 'new_id' => $record->getKey(), 'updated_at' => now(), 'created_at' => now()],
        );
    }

    /**
     * The record migrated from this legacy row (trashed ones too), or null.
     *
     * @template T of Model
     *
     * @param  class-string<T>  $model
     * @return T|null
     */
    public function record(string $legacyTable, int|string $legacyId, string $model): ?Model
    {
        $id = $this->find($legacyTable, $legacyId);

        if ($id === null) {
            return null;
        }

        $query = $model::query()->withoutGlobalScopes();

        return $query->find($id);
    }
}
