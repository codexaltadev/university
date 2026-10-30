<?php

namespace Modules\Office\Providers;

use Illuminate\Support\Facades\View;
use Modules\Office\Models\Office;
use Modules\Office\Support\OfficePage;
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

        // An office page's parts for any theme's version of it (themes hold no PHP logic, T-1)
        View::composer(['office::microsite.home', 'office::team'], fn ($view) => $view->with('page', new OfficePage($view->getData()['entity'])));

        // A page of an office's own site — and its notice board — opens in the office's frame (DIU /registrar-office/{slug}): $office
        View::composer(['theme::templates.*', 'notice::index'], function ($view): void {
            $entity = View::shared('context')?->entity;
            $view->with('office', $entity instanceof Office && $entity->has_microsite ? new OfficePage($entity) : null);
        });

        // Honour Board and Contact blocks: the office whose site the page is on (none elsewhere: the block shows nothing)
        View::composer('office::blocks.*', function ($view): void {
            $entity = View::shared('context')?->entity;
            $view->with('office', $entity instanceof Office ? new OfficePage($entity) : null);
        });
    }
}
