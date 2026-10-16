<?php

namespace Modules\Documents\Providers;

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
    }
}
