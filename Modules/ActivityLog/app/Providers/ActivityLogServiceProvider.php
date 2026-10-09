<?php

namespace Modules\ActivityLog\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\ActivityLog\Filament\Resources\Activities\ActivityResource;
use Modules\ActivityLog\Policies\ActivityPolicy;
use Platform\Activity\Activity;
use Platform\Modules\ModuleServiceProvider;

class ActivityLogServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'ActivityLog';

    protected string $nameLower = 'activitylog';

    public function register(): void
    {
        parent::register();

        // Read-only: the role editor offers only "view any" and "view"
        config(['filament-shield.resources.manage.'.ActivityResource::class => ['viewAny', 'view']]);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Activity::class, ActivityPolicy::class);
    }
}
