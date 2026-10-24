<?php

namespace Modules\International\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\View;
use Modules\International\Console\ExpiringAgreementsCommand;
use Modules\International\Support\InternationalWebsite;
use Modules\International\Support\VisitorsPage;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class InternationalServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'International';

    protected string $nameLower = 'international';

    public function register(): void
    {
        parent::register();
        $this->app->singleton(InternationalWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(InternationalWebsite::class)->respond($rest, $context, $locale));
        $this->commands([ExpiringAgreementsCommand::class]);
        View::composer('international::visitors', fn ($view) => $view->with('page', new VisitorsPage($view->getData())));   // themes hold no PHP logic (T-1)
        $this->app->booted(fn () => $this->app->make(Schedule::class)->command('international:expiring')->dailyAt('06:10')->withoutOverlapping());   // FR-INTL-03
    }
}
