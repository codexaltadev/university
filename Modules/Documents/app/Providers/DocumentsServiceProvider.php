<?php

namespace Modules\Documents\Providers;

use Illuminate\Support\Facades\View;
use Modules\Documents\Support\DocumentsPage;
use Modules\Documents\Support\DocumentsWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class DocumentsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Documents';

    protected string $nameLower = 'documents';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(DocumentsWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Website: /downloads[/{slug}[/file]], also under a microsite (FR-DOC-04)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(DocumentsWebsite::class)->respond($rest, $context, $locale));

        // The downloads pages' parts for any theme's version of them (themes hold no PHP logic, T-1)
        View::composer(['documents::index', 'documents::show'], fn ($view) => $view->with('page', new DocumentsPage($view->getData())));
    }
}
