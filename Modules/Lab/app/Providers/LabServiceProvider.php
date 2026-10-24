<?php

namespace Modules\Lab\Providers;

use Illuminate\Support\Facades\View;
use Modules\Lab\Models\Lab;
use Modules\Lab\Support\LabPage;
use Modules\Lab\Support\LabsPage;
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

        // A lab page's and the labs listings' parts for any theme's version of them (themes hold no PHP logic, T-1)
        View::composer('lab::microsite.home', fn ($view) => $view->with('page', new LabPage($view->getData()['entity'])));
        View::composer('lab::listing', fn ($view) => $view->with('page', new LabsPage($view->getData()['labs'], $view->getData()['place'] ?? null)));
    }
}
