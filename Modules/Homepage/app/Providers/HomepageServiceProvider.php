<?php

namespace Modules\Homepage\Providers;

use Illuminate\Support\Facades\Event;
use Modules\Homepage\Filament\Pages\ManageHomepage;
use Modules\Homepage\Support\Homepages;
use Platform\Context\SiteContext;
use Platform\Models\BaseModel;
use Platform\Modules\ModuleServiceProvider;
use Platform\Theme\SiteData;

class HomepageServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Homepage';

    protected string $nameLower = 'homepage';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(Homepages::class);

        // Guarded by Manage:Homepage / ManageMain:Homepage, not a page permission
        config()->push('filament-shield.pages.exclude', ManageHomepage::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Section data follows content changes (SAD Part B §9)
        Event::listen(['eloquent.saved: *', 'eloquent.deleted: *', 'eloquent.restored: *'], function (string $event, array $models): void {
            if (($models[0] ?? null) instanceof BaseModel) {
                Homepages::flush();
            }
        });

        // <x-platform::homepage> on the website (C-06); null = the theme's default
        $this->app->make(SiteData::class)->provide('homepage', fn (SiteContext $context): ?array => $this->app->make(Homepages::class)->forOwner($context->key()));
    }
}
