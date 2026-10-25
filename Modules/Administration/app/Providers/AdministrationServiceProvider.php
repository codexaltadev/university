<?php

namespace Modules\Administration\Providers;

use Illuminate\Support\Facades\View;
use Modules\Administration\Support\AdministrationPage;
use Modules\Administration\Support\AdministrationWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class AdministrationServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Administration';

    protected string $nameLower = 'administration';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(AdministrationWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Website: /administration, /administration/{slug} (FR-ADMN-04/05)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(AdministrationWebsite::class)->respond($rest, $context, $locale));
        // What a theme shows on them, ready (DIU's /admin/{section}; themes hold no logic, T-1)
        View::composer(['administration::directory', 'administration::profile'], fn ($view) => $view->with('page', new AdministrationPage($view->getData())));
    }
}
