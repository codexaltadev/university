<?php

namespace Modules\Menu\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;
use Platform\Context\FallbackResolver;
use Platform\Context\SiteContext;

/**
 * Menus for the website (FR-MENU-07/10/12): the menu that applies at a location in a
 * context (own, parent's … main site — BRL-MENU-01), as a nested list of visible
 * items. Cached per menu and language; any menu or content change starts a new cache generation.
 *
 * Item: ['title', 'url', 'new_tab', 'icon', 'class', 'children' => [...]]; a heading has url null.
 */
class MenuTree
{
    public const VERSION_KEY = 'menu:version';

    public function __construct(protected MenuLinks $links) {}

    /** @return list<array<string, mixed>> */
    public function forLocation(string $location, SiteContext $context, ?string $locale = null): array
    {
        $menu = app(FallbackResolver::class)->resolve('menu', $location, $context);

        return $menu instanceof Menu ? $this->of($menu, $locale) : [];
    }

    /** @return list<array<string, mixed>> */
    public function of(Menu $menu, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $version = Cache::get(self::VERSION_KEY, 0);

        return Cache::rememberForever("menu:{$menu->id}:{$version}:{$locale}", fn (): array => $this->build($menu->items()->get(), null, $locale));
    }

    /** Menu or linked content changed (FR-MENU-12, AC-MENU-03). */
    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, Cache::get(self::VERSION_KEY, 0) + 1);
        app(FallbackResolver::class)->flush();
    }

    /**
     * @param  Collection<int, MenuItem>  $items
     * @return list<array<string, mixed>>
     */
    protected function build(Collection $items, ?int $parentId, string $locale): array
    {
        return $items->where('parent_id', $parentId)
            ->filter(fn (MenuItem $item): bool => $this->links->isVisible($item))   // a hidden parent hides its children
            ->map(fn (MenuItem $item): array => [
                'title' => $this->links->label($item, $locale),
                'url' => $this->links->href($item, $locale),
                'new_tab' => $item->open_in_new_tab,
                'icon' => $item->icon,
                'class' => $item->css_class,
                'children' => $this->build($items, $item->id, $locale),
            ])
            ->values()->all();
    }
}
