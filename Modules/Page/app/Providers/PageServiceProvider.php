<?php

namespace Modules\Page\Providers;

use Modules\Page\Support\PageRouter;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class PageServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Page';

    protected string $nameLower = 'page';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(PageRouter::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Website: /about, /department/cse/about/history (FR-PAGE-03/05)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(PageRouter::class)->respond($rest, $context, $locale));
    }
}
