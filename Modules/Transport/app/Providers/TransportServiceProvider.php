<?php

namespace Modules\Transport\Providers;

use Modules\Transport\Support\TransportApi;
use Modules\Transport\Support\TransportWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class TransportServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Transport';

    protected string $nameLower = 'transport';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(TransportApi::class);
        $this->app->singleton(TransportWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Website: /transport, from the live transport system's API (DB-06 v1.1 §2)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(TransportWebsite::class)->respond($rest, $context, $locale));
    }
}
