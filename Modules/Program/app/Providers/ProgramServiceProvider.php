<?php

namespace Modules\Program\Providers;

use Modules\Program\Models\Program;
use Modules\Program\Support\ProgramWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class ProgramServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Program';

    protected string $nameLower = 'program';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(ProgramWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Website: /programs (FR-PROG-12); the microsite /program/{slug} is the kernel's
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(ProgramWebsite::class)->respond($rest, $context, $locale));

        // FR-PROG-11: every new Program starts with its own counters
        Program::created(fn (Program $program) => ProgramWebsite::createStatSet($program));
    }
}
