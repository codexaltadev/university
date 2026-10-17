<?php

namespace Modules\Lab\Providers;

use Modules\Lab\Models\Lab;
use Modules\Lab\Support\LabWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class LabServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Lab';

    protected string $nameLower = 'lab';

    public function register(): void
    {
        parent::register();
        $this->app->singleton(LabWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(LabWebsite::class)->respond($rest, $context, $locale));
        Lab::created(fn (Lab $lab) => LabWebsite::createStatSet($lab));   // FR-LAB-11
    }
}
