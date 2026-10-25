<?php

namespace Modules\Facilities\Providers;

use Illuminate\Support\Facades\View;
use Modules\Facilities\Support\FacilitiesPage;
use Modules\Facilities\Support\FacilitiesWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class FacilitiesServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Facilities';

    protected string $nameLower = 'facilities';

    public function register(): void
    {
        parent::register();
        $this->app->singleton(FacilitiesWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(FacilitiesWebsite::class)->respond($rest, $context, $locale));
        // What a theme shows on them, ready (themes hold no logic, T-1)
        View::composer(['facilities::index', 'facilities::show'], fn ($view) => $view->with('page', new FacilitiesPage($view->getData())));
    }
}
