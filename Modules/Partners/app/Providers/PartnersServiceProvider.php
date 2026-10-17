<?php

namespace Modules\Partners\Providers;

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
    }
}
