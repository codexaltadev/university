<?php

namespace Modules\ThemeManager\Providers;

use Illuminate\Support\Facades\Route;
use Modules\ThemeManager\Filament\Pages\ManageDesign;
use Modules\ThemeManager\Filament\Pages\ManageLayouts;
use Modules\ThemeManager\Filament\Pages\ManageThemes;
use Platform\Modules\ModuleServiceProvider;

class ThemeManagerServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'ThemeManager';

    protected string $nameLower = 'thememanager';

    public function register(): void
    {
        parent::register();

        // Guarded by View / Install / Activate / Customize:Theme (SRS C-06), not page permissions
        foreach ([ManageThemes::class, ManageDesign::class, ManageLayouts::class] as $page) {
            config()->push('filament-shield.pages.exclude', $page);
        }
    }

    public function boot(): void
    {
        parent::boot();

        if (! $this->app->routesAreCached()) {
            Route::middleware('web')->group(module_path($this->name, 'routes/web.php'));
        }
    }
}
