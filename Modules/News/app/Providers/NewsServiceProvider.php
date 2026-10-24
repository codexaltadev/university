<?php

namespace Modules\News\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Modules\News\Models\Post;
use Modules\News\Policies\PostPolicy;
use Modules\News\Support\NewsPage;
use Modules\News\Support\NewsWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class NewsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'News';

    protected string $nameLower = 'news';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(NewsWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Post::class, PostPolicy::class);

        $this->app->make(Website::class)->handle(
            fn (string $rest, SiteContext $context, string $locale) => $this->app->make(NewsWebsite::class)->respond($rest, $context, $locale)
        );

        // The news pages' parts for any theme's version of them (themes hold no PHP logic, T-1)
        View::composer(['news::index', 'news::show'], fn ($view) => $view->with('page', new NewsPage($view->getData())));
    }
}
