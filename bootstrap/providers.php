<?php

return [
    // Kernel first: ZIP-installed modules must be autoloadable before nwidart registers them
    Platform\PlatformServiceProvider::class,
    // Not auto-discovered (composer.json "dont-discover") so it runs after the kernel
    Nwidart\Modules\LaravelModulesServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
];
