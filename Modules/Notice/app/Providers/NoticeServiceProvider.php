<?php

namespace Modules\Notice\Providers;

use Modules\Notice\Support\NoticeWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class NoticeServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Notice';

    protected string $nameLower = 'notice';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(NoticeWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Website: /notices[/{slug}], also under a microsite (FR-NOT-04/05)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(NoticeWebsite::class)->respond($rest, $context, $locale));
    }
}
