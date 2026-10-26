<?php

namespace Modules\Transport\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Modules\Transport\Support\Timetable;
use Modules\Transport\Support\TransportView;
use Modules\Transport\Support\TransportWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class TransportServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Transport';

    protected string $nameLower = 'transport';

    public function register(): void
    {
        parent::register();

        $this->app->bind(Timetable::class);
        $this->app->singleton(TransportWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        View::composer('transport::index', fn ($view) => $view->with('transport', new TransportView($view->getData())));   // for themes (T-1)

        // Website: /transport (DB-06 v1.2 §2); the public API is in routes/web.php
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(TransportWebsite::class)->respond($rest, $context, $locale));

        if (! $this->app->routesAreCached()) {
            Route::group([], module_path($this->name, 'routes/web.php'));   // the public API: no session, no cookies
        }
    }
}
