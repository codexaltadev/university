<?php

namespace Modules\Leadership\Providers;

use Modules\Leadership\Support\LeadershipWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class LeadershipServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Leadership';

    protected string $nameLower = 'leadership';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(LeadershipWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Website: /leadership, /leadership/former, /leadership/{slug} (FR-LEAD-03..05)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(LeadershipWebsite::class)->respond($rest, $context, $locale));
    }
}
