<?php

namespace Modules\HeaderFooter\Providers;

use Modules\HeaderFooter\Filament\Pages\ManageHeaderFooter;
use Modules\HeaderFooter\Models\Region;
use Modules\HeaderFooter\Support\Regions;
use Platform\Context\FallbackResolver;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Scope\UserScopeFilter;
use Platform\Theme\SiteData;

class HeaderFooterServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'HeaderFooter';

    protected string $nameLower = 'headerfooter';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(Regions::class);

        // Guarded by Manage:HeaderFooter / ManageMain:HeaderFooter, not a page permission
        config()->push('filament-shield.pages.exclude', ManageHeaderFooter::class);
    }

    public function boot(): void
    {
        parent::boot();

        // FR-HF-05: own → parent … → main site, one resource per region (SAD Part B §6.2)
        foreach (Region::REGIONS as $region) {
            $this->app->make(FallbackResolver::class)->store($region, fn (string $type, int $id): ?Region => Region::query()
                ->withoutGlobalScope(UserScopeFilter::class)
                ->where('owner_type', $type)->where('owner_id', $id)->where('region', $region)
                ->where('is_active', true)->first());
        }

        // <x-platform::slot name="header|footer|topbar"> on the website (C-06)
        $this->app->make(SiteData::class)->provide('region', fn (string $region, SiteContext $context): ?array => $this->app->make(Regions::class)->forContext($region, $context));
    }
}
