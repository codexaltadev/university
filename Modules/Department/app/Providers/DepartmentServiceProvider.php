<?php

namespace Modules\Department\Providers;

use Modules\Department\Models\Department;
use Modules\Department\Support\DepartmentWebsite;
use Modules\Faculty\Models\Faculty;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class DepartmentServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Department';

    protected string $nameLower = 'department';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(DepartmentWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Website: /departments (FR-DEPT-06); the microsite /department/{slug} is the kernel's
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(DepartmentWebsite::class)->respond($rest, $context, $locale));

        // A Faculty's departments (Faculty Hub, FR-GEN-86) — added here, the Faculty module does not know this one
        Faculty::resolveRelationUsing('departments', fn (Faculty $faculty) => $faculty->hasMany(Department::class));

        // FR-DEPT-05: every new Department starts with its own counters
        Department::created(fn (Department $department) => DepartmentWebsite::createStatSet($department));
    }
}
