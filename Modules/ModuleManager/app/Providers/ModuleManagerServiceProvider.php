<?php

namespace Modules\ModuleManager\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\ModuleManager\Filament\Resources\Modules\ModuleResource;
use Modules\ModuleManager\Policies\CoreModulePolicy;
use Platform\Models\CoreModule;
use Platform\Modules\ModuleServiceProvider;

class ModuleManagerServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'ModuleManager';

    protected string $nameLower = 'modulemanager';

    public function register(): void
    {
        parent::register();

        // Super Admin only: keep it out of the role editor and permission sync
        config()->push('filament-shield.resources.exclude', ModuleResource::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(CoreModule::class, CoreModulePolicy::class);
    }
}
