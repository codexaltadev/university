<?php

namespace Modules\Role\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Role\Policies\RolePolicy;
use Platform\Modules\ModuleServiceProvider;
use Spatie\Permission\Models\Role;

class RoleServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Role';

    protected string $nameLower = 'role';

    public function boot(): void
    {
        parent::boot();

        // Spatie's Role model is outside Laravel's policy discovery
        Gate::policy(Role::class, RolePolicy::class);
    }
}
