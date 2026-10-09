<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use Legacy\Migration\LegacyMigrationServiceProvider;
use Nwidart\Modules\LaravelModulesServiceProvider;
use Platform\PlatformServiceProvider;

return [
    // Kernel first: ZIP-installed modules must be autoloadable before nwidart registers them
    PlatformServiceProvider::class,
    // Not auto-discovered (composer.json "dont-discover") so it runs after the kernel
    LaravelModulesServiceProvider::class,
    AppServiceProvider::class,
    AdminPanelProvider::class,
    // DIU data migration (Data Migration Plan §4): not part of the product, only its commands
    LegacyMigrationServiceProvider::class,
];
