<?php

namespace Modules\Gallery\Providers;

use Illuminate\Support\Facades\View;
use Modules\Gallery\Support\GalleryPage;
use Modules\Gallery\Support\GalleryWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class GalleryServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Gallery';

    protected string $nameLower = 'gallery';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(GalleryWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Website: /gallery[/{slug}], also under a microsite (FR-GAL-05)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(GalleryWebsite::class)->respond($rest, $context, $locale));

        // The gallery's parts for any theme's version of it (themes hold no PHP logic, T-1)
        View::composer(['gallery::index', 'gallery::show'], fn ($view) => $view->with('page', new GalleryPage($view->getData(), view()->shared('context') ?? SiteContext::site())));
    }
}
