<?php

namespace Modules\Events\Providers;

use Illuminate\Support\Facades\View;
use Modules\Events\Support\EventPage;
use Modules\Events\Support\EventsPage;
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

        // The listing's and an event's parts for any theme's version of them (themes hold no PHP logic, T-1)
        View::composer('events::index', fn ($view) => $view->with('page', new EventsPage($view->getData())));
        View::composer('events::show', fn ($view) => $view->with('page', new EventPage($view->getData()['event'], $view->getData()['base'] ?? '')));
    }
}
