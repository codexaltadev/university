<?php

namespace Modules\Partners\Providers;

use Illuminate\Support\Facades\View;
use Modules\Partners\Support\PartnersPage;
use Modules\Partners\Support\PartnersWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class PartnersServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Partners';

    protected string $nameLower = 'partners';

    public function register(): void
    {
        parent::register();
        $this->app->singleton(PartnersWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(PartnersWebsite::class)->respond($rest, $context, $locale));
        // What a theme shows on them, ready (DIU's /mps and /discount-partners; themes hold no logic, T-1)
        View::composer(['partners::index', 'partners::discounts'], fn ($view) => $view->with('page', new PartnersPage($view->getData())));
    }
}
