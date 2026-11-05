<?php

namespace Modules\Page\Providers;

use Illuminate\Support\Facades\View;
use Modules\Page\Blocks\JobsBlock;
use Modules\Page\Blocks\LocationBlock;
use Modules\Page\Models\Page;
use Modules\Page\Support\FactsPage;
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

        // The Location block's campus from Settings › Contact (the module's view or the theme's)
        View::composer('page::blocks.location', fn ($view) => $view->with('campus', LocationBlock::campus((array) ($view->getData()['data'] ?? []))));

        // The Jobs block's openings from SkillJobs, with the types and levels to filter by
        View::composer('page::blocks.jobs', function ($view): void {
            $jobs = JobsBlock::jobs((array) ($view->getData()['data'] ?? []));
            $view->with(['jobs' => $jobs, 'index' => array_map(fn (array $j): array => [$j['type'], $j['level'], $j['expired']], $jobs ?? []), 'types' => collect($jobs)->pluck('type')->filter()->unique()->values()->all(), 'levels' => collect($jobs)->pluck('level')->filter()->unique()->values()->all()]);
        });

        // The key facts block, shaped for a theme (UI/UX Part B §5.8)
        View::composer('page::blocks.facts', fn ($view) => $view->with('facts', new FactsPage((array) ($view->getData()['data'] ?? []))));

        // Website: /about, /fsit/cse/about/history (FR-PAGE-03/05)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(PageRouter::class)->respond($rest, $context, $locale));
    }
}
