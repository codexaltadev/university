<?php

namespace Modules\Committee\Providers;

use Illuminate\Support\Facades\View;
use Modules\Committee\Support\CommitteesPage;
use Modules\Committee\Support\CommitteeWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class CommitteeServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Committee';

    protected string $nameLower = 'committee';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(CommitteeWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Website: /committees[/{slug}], also under a microsite (FR-COM-04/06)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(CommitteeWebsite::class)->respond($rest, $context, $locale));
        // What a theme shows on them, ready (DIU's /committee/{slug}; themes hold no logic, T-1)
        View::composer(['committee::index', 'committee::show'], fn ($view) => $view->with('page', new CommitteesPage($view->getData())));
    }
}
