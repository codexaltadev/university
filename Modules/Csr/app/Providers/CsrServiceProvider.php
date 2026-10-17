<?php

namespace Modules\Csr\Providers;

use Modules\Csr\Support\CsrWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class CsrServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Csr';

    protected string $nameLower = 'csr';

    public function register(): void
    {
        parent::register();
        $this->app->singleton(CsrWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(CsrWebsite::class)->respond($rest, $context, $locale));
    }
}
