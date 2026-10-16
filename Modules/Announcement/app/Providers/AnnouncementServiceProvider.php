<?php

namespace Modules\Announcement\Providers;

use Modules\Announcement\Support\Announcements;
use Modules\Announcement\Support\CurrentPage;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Theme\SiteData;

class AnnouncementServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Announcement';

    protected string $nameLower = 'announcement';

    public function register(): void
    {
        parent::register();

        $this->app->scoped(Announcements::class);   // loaded once per request
        $this->app->singleton(CurrentPage::class);
    }

    public function boot(): void
    {
        parent::boot();

        // The layout slots this module fills while it is active (DB-05 §15 v1.11, Theme Guide §5)
        foreach (['announcement', 'ticker', 'popup'] as $slot) {
            $this->app->make(SiteData::class)->provide('slot:'.$slot, fn (?SiteContext $context = null) => $this->app->make(Announcements::class)->slot($slot, $context ?? SiteContext::site()));
        }
    }
}
