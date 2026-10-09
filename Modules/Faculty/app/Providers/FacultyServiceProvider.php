<?php

namespace Modules\Faculty\Providers;

use Modules\Faculty\Models\Faculty;
use Modules\Faculty\Support\FacultyWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class FacultyServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Faculty';

    protected string $nameLower = 'faculty';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(FacultyWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Website: /faculties (FR-FAC-05); the microsite /faculty/{slug} is the kernel's
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(FacultyWebsite::class)->respond($rest, $context, $locale));

        // FR-FAC-04: every new Faculty starts with its own counters
        Faculty::created(fn (Faculty $faculty) => FacultyWebsite::createStatSet($faculty));
    }
}
