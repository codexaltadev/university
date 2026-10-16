<?php

namespace Modules\Achievements\Providers;

use Modules\Achievements\Support\AchievementsWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class AchievementsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Achievements';

    protected string $nameLower = 'achievements';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(AchievementsWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Website: /achievements[/{slug}], also under a microsite (FR-ACH-02)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(AchievementsWebsite::class)->respond($rest, $context, $locale));
    }
}
