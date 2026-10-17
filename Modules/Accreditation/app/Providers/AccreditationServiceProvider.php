<?php

namespace Modules\Accreditation\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Accreditation\Console\ExpiringAccreditationsCommand;
use Modules\Accreditation\Support\AccreditationWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class AccreditationServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Accreditation';

    protected string $nameLower = 'accreditation';

    public function register(): void
    {
        parent::register();
        $this->app->singleton(AccreditationWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(AccreditationWebsite::class)->respond($rest, $context, $locale));
        $this->commands([ExpiringAccreditationsCommand::class]);
        $this->app->booted(fn () => $this->app->make(Schedule::class)->command('accreditation:expiring')->dailyAt('06:00')->withoutOverlapping());   // FR-ACR-02
    }
}
