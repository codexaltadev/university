<?php

namespace Platform;

use Illuminate\Support\ServiceProvider;
use Platform\Modules\ModulePermissions;
use Platform\Permissions\EntityPermissions;
use Platform\Scope\ScopeResolver;

class PlatformServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ScopeResolver::class);
        $this->app->singleton(EntityPermissions::class);
        $this->app->singleton(ModulePermissions::class);
    }
}
