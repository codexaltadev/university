<?php

namespace Legacy\Migration;

use Illuminate\Support\ServiceProvider;
use Legacy\Migration\Console\MigrateCommand;

/**
 * The DIU data migration tool (Data Migration Plan §4): reads project_diu through the
 * "legacy" connection and writes through the modules' models. Not part of the product.
 */
class LegacyMigrationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');   // migration_id_map

        if ($this->app->runningInConsole()) {
            $this->commands([MigrateCommand::class]);
        }
    }
}
