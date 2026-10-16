<?php

namespace Modules\Events\Providers;

use Modules\Events\Support\EventsWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class EventsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Events';

    protected string $nameLower = 'events';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(EventsWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Website: /events[/{slug}[.ics]], also under a microsite (FR-EVT-03/07)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(EventsWebsite::class)->respond($rest, $context, $locale));
    }
}
