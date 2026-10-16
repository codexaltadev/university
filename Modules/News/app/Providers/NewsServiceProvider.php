<?php

namespace Modules\News\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\News\Models\Post;
use Modules\News\Policies\PostPolicy;
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
    }
}
