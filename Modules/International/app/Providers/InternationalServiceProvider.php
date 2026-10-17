<?php

namespace Modules\International\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\International\Console\ExpiringAgreementsCommand;
use Modules\International\Support\InternationalWebsite;
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
        $this->app->booted(fn () => $this->app->make(Schedule::class)->command('international:expiring')->dailyAt('06:10')->withoutOverlapping());   // FR-INTL-03
    }
}
