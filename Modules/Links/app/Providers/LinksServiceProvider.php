<?php

namespace Modules\Links\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Links\Console\CheckLinksCommand;
use Modules\Links\Support\LinksWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class LinksServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Links';

    protected string $nameLower = 'links';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(LinksWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Website: /links, also under a microsite (FR-LINK-02)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(LinksWebsite::class)->respond($rest, $context, $locale));

        $this->commands([CheckLinksCommand::class]);
        $this->app->booted(fn () => $this->app->make(Schedule::class)->command('links:check')->weeklyOn(1, '04:00')->withoutOverlapping());   // FR-LINK-03
    }
}
