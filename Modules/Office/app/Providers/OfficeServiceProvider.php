<?php

namespace Modules\Office\Providers;

use Modules\Office\Models\Office;
use Modules\Office\Support\OfficeWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class OfficeServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Office';

    protected string $nameLower = 'office';

    /** @var string[] */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->singleton(OfficeWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Website: /offices, /services, /support-contacts (FR-OFF-06/07/09); the microsite comes from the kernel
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(OfficeWebsite::class)->respond($rest, $context, $locale));

        // FR-OFF-14: a new office gets its own counters
        Office::created(fn (Office $office) => OfficeWebsite::createStatSet($office));
    }
}
