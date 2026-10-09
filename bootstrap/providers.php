<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use Nwidart\Modules\LaravelModulesServiceProvider;
use Platform\PlatformServiceProvider;

return [
    // Kernel first: ZIP-installed modules must be autoloadable before nwidart registers them
    PlatformServiceProvider::class,
    // Not auto-discovered (composer.json "dont-discover") so it runs after the kernel
    LaravelModulesServiceProvider::class,
    AppServiceProvider::class,
    AdminPanelProvider::class,
];
