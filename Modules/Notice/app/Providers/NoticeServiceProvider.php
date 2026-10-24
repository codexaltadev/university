<?php

namespace Modules\Notice\Providers;

use Illuminate\Support\Facades\View;
use Modules\Notice\Support\NoticeBoardPage;
use Modules\Notice\Support\NoticePage;
use Modules\Notice\Support\NoticeWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class NoticeServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Notice';

    protected string $nameLower = 'notice';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(NoticeWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Website: /notices[/{slug}], also under a microsite (FR-NOT-04/05)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(NoticeWebsite::class)->respond($rest, $context, $locale));

        // The board's and a notice's parts for any theme's version of them (themes hold no PHP logic, T-1)
        View::composer('notice::index', fn ($view) => $view->with('page', new NoticeBoardPage($view->getData())));
        View::composer('notice::show', fn ($view) => $view->with('page', new NoticePage($view->getData()['notice'], $view->getData()['attachments'], $view->getData()['base'] ?? '', $view->getData()['locale'] ?? null)));
    }
}
