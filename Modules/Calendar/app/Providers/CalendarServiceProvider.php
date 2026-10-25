<?php

namespace Modules\Calendar\Providers;

use Illuminate\Support\Facades\View;
use Modules\Calendar\Support\CalendarPage;
use Modules\Calendar\Support\CalendarWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class CalendarServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Calendar';

    protected string $nameLower = 'calendar';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(CalendarWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Website: /academic-calendar, /academic-calendar/{slug}(.ics), /{faculty}/academic-calendar (FR-CAL-04/06/09)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(CalendarWebsite::class)->respond($rest, $context, $locale));

        // The calendar pages' parts for any theme's version of them (themes hold no PHP logic, T-1)
        View::composer(['calendar::index', 'calendar::show'], fn ($view) => $view->with('page', new CalendarPage($view->getData())));
    }
}
