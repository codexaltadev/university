<?php

namespace Modules\Page\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Modules\Page\Blocks\PageBlocks;
use Modules\Page\Models\Page;
use Platform\Context\SiteContext;
use Platform\Scope\UserScopeFilter;
use Platform\Seo\Seo;
use Platform\Slugs\Slug;
use Platform\Theme\LocationMenus;
use Platform\Theme\Themes;

/**
 * Pages on the website (FR-PAGE-03/04/05): the rest of the address after the
 * microsite part, page by page down the parents of that owner — e.g. CSE's
 * "about/history". Only active, published, not expired pages whose parents are
 * visible too. An old address (changed slug) answers 301 to the current one
 * (FR-GEN-32, FR-SEO-07, AC-SEO-01).
 */
class PageRouter
{
    /** @return array{0: Page, 1: bool}|null [page, address is current] */
    public function find(string $rest, SiteContext $context, string $locale): ?array
    {
        $segments = array_values(array_filter(explode('/', trim($rest, '/')), 'strlen'));
        $type = (new Page)->getMorphClass();
        $parentId = null;
        $page = null;
        $current = true;

        foreach ($segments as $segment) {
            $slugs = Slug::query()->where('sluggable_type', $type)->where('locale', $locale)->where('slug', $segment)->get();

            if ($slugs->isEmpty()) {
                return null;
            }

            $page = Page::query()
                ->withoutGlobalScope(UserScopeFilter::class)
                ->frontendVisible()
                ->ownedBy($context->key())
                ->where('parent_id', $parentId)
                ->whereIn('id', $slugs->pluck('sluggable_id'))
                ->first();

            if ($page === null) {
                return null;
            }

            $current = $current && (bool) $slugs->firstWhere('sluggable_id', $page->id)?->is_current;
            $parentId = $page->id;
        }

        return $page ? [$page, $current] : null;
    }

    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        $found = $this->find($rest, $context, $locale);

        if ($found === null) {
            return null;
        }

        [$page, $current] = $found;

        if (! $current) {
            return redirect(Seo::localized($page->url($locale), $locale), 301);
        }

        app(Seo::class)->forRecord($page);
        app(LocationMenus::class)->use('sidebar', $page->sidebarMenuId());   // its own sidebar menu, or its parent's (DB-02 v0.2 §8.1)

        $templates = app(Themes::class)->manifest()->pageTemplates();
        $template = in_array($page->template, $templates, true) ? $page->template : 'default';

        return response()->view(
            view()->exists("theme::templates.{$template}") ? "theme::templates.{$template}" : 'theme::templates.default',
            ['page' => $page, 'body' => PageBlocks::render((array) $page->getTranslation('content', $locale, true), $locale)],
        );
    }
}
