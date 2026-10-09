<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fast database reset for module lifecycle tests. Real installs run DDL, so a
 * transaction cannot undo them; instead of migrate:fresh before every test
 * (~15 s on WSL), the schema is built once and later tests drop the tables test
 * modules created and empty the rest. A full rebuild happens only when a test
 * removed a baseline table (e.g. uninstalling a bundled module with its data).
 */
class LifecycleDatabase
{
    /** @var list<string>|null */
    protected static ?array $tables = null;

    /** @var list<string> */
    protected static array $migrations = [];

    public static function reset(): void
    {
        $current = self::tables();

        if (self::$tables === null || array_diff(self::$tables, $current) !== []) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            self::$tables = self::tables();
            self::$migrations = DB::table('migrations')->pluck('migration')->all();

            return;
        }

        Schema::disableForeignKeyConstraints();

        foreach (array_diff($current, self::$tables) as $table) {
            Schema::drop($table);
        }

        DB::table('migrations')->whereNotIn('migration', self::$migrations)->delete();

        foreach (self::$tables as $table) {
            if ($table !== 'migrations') {
                DB::table($table)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    /** @return list<string> tables of the test database only (getTables() lists every schema the user can see) */
    protected static function tables(): array
    {
        return array_column(
            DB::select("SELECT TABLE_NAME AS name FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'"),
            'name',
        );
    }
}
