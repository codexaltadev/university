<?php

/*
| Phase 1E — every reference column is indexed (SAD Part A §6.6 IX-1..IX-3).
| A column passes when it leads an index, or when its polymorphic partner
| (x_id ↔ x_type) leads one with it right behind.
*/

use Illuminate\Support\Facades\DB;

/** Framework tables we do not own. */
const UNOWNED_TABLES = ['migrations', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'];

it('indexes every *_id and *_type column (IX-2)', function () {
    $columns = DB::select(
        "SELECT TABLE_NAME AS t, COLUMN_NAME AS c FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND (COLUMN_NAME LIKE '%\\_id' OR COLUMN_NAME LIKE '%\\_type')"
    );

    $indexes = collect(DB::select(
        'SELECT TABLE_NAME AS t, INDEX_NAME AS i, SEQ_IN_INDEX AS s, COLUMN_NAME AS c
         FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX'
    ))->groupBy(fn ($row) => $row->t.'|'.$row->i)->map(fn ($rows) => $rows->pluck('c')->all());

    $missing = [];

    foreach ($columns as $column) {
        if (in_array($column->t, UNOWNED_TABLES, true)) {
            continue;
        }

        // morph pair partner: x_id ↔ x_type, indexed together in either order
        $partner = str_ends_with($column->c, '_id')
            ? substr($column->c, 0, -3).'_type'
            : substr($column->c, 0, -5).'_id';

        $covered = $indexes->contains(function (array $cols, string $key) use ($column, $partner): bool {
            if (! str_starts_with($key, $column->t.'|')) {
                return false;
            }

            return $cols[0] === $column->c || ($cols[0] === $partner && ($cols[1] ?? null) === $column->c);
        });

        if (! $covered) {
            $missing[] = "{$column->t}.{$column->c}";
        }
    }

    expect($missing)->toBe([]);
});
