<?php

namespace Modules\Scholarship\Providers;

use Modules\Scholarship\Support\ScholarshipWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class ScholarshipServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Scholarship';

    protected string $nameLower = 'scholarship';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(ScholarshipWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Website: /scholarships, /scholarships/{slug} (FR-FEE-10)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(ScholarshipWebsite::class)->respond($rest, $context, $locale));
    }
}
