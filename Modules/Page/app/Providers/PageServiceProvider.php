<?php

namespace Modules\Page\Providers;

use Modules\Page\Models\Page;
use Modules\Page\Support\PageRouter;
use Platform\Context\SiteContext;
use Platform\Context\UrlRules;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class PageServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Page';

    protected string $nameLower = 'page';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(PageRouter::class);
    }

    public function boot(): void
    {
        parent::boot();

        // FR-GEN-35: the kernel checks entity addresses against top-level pages (by owner, or any owner of a kind)
        UrlRules::pagesUsing(fn (?string $ownerKey, ?string $ownerType, string $slug): bool => Page::query()
            ->withoutGlobalScopes()->whereNull('parent_id')
            ->when($ownerKey, fn ($q) => $q->ownedBy($ownerKey))
            ->when(! $ownerKey && $ownerType, fn ($q) => $q->where('owner_type', $ownerType))
            ->whereHas('slugs', fn ($q) => $q->where('slug', $slug)->where('is_current', true))
            ->exists());

        // Website: /about, /fsit/cse/about/history (FR-PAGE-03/05)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(PageRouter::class)->respond($rest, $context, $locale));
    }
}
