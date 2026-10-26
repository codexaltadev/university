<?php

namespace Modules\Seo\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Modules\Seo\Http\HandleRedirects;
use Modules\Seo\Models\NotFoundLog;
use Modules\Seo\Models\SearchTerm;
use Modules\Seo\Support\PopularSearches;
use Modules\Seo\Support\Redirects;
use Platform\Events\SearchPerformed;
use Platform\Modules\ModuleServiceProvider;

class SeoServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Seo';

    protected string $nameLower = 'seo';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(Redirects::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Before routing: redirects even for addresses no route knows (FR-SEO-04/06)
        $this->app->make(Kernel::class)->pushMiddleware(HandleRedirects::class);

        // What visitors search (and miss), and the popular searches the search window and page offer (Phase 4.7g)
        Event::listen(fn (SearchPerformed $event) => SearchTerm::record($event->query, $event->total));
        View::composer(['theme::livewire.instant-search', 'theme::search'], fn ($view) => $view->with('popular', PopularSearches::links()));
    }

    /** DB-02 §9.3: resolved 404 rows go after 90 days. */
    protected function configureSchedules(Schedule $schedule): void
    {
        $schedule->call(fn () => NotFoundLog::clean())->dailyAt('03:30')->name('seo:404-clean');
        $schedule->call(fn () => SearchTerm::clean())->dailyAt('03:35')->name('seo:search-terms-clean');
    }
}
