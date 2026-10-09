<?php

namespace Modules\Menu\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;
use Platform\Context\FallbackResolver;
use Platform\Context\SiteContext;
use Platform\Media\MediaItem;

/**
 * Menus for the website (FR-MENU-07/10/12): the menu that applies at a location in a
 * context (own, parent's … main site — BRL-MENU-01), as a nested list of visible
 * items. Cached per menu and language; any menu or content change starts a new cache generation.
 *
 * Item: ['title', 'url', 'new_tab', 'icon', 'class', 'children' => [...]]; a heading has url null.
 * Top items also: 'mega' (bool) and, for mega panels, 'columns', 'image', 'description', 'cta'.
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

    /**
     * The menu at a location with how it opens: ['style' => dropdown|multilevel|mega, 'items' => [...]].
     *
     * @return array{style: ?string, items: list<array<string, mixed>>}
     */
    public function menuAt(string $location, SiteContext $context, ?string $locale = null): array
    {
        $menu = app(FallbackResolver::class)->resolve('menu', $location, $context);

        return $menu instanceof Menu ? ['style' => $menu->style, 'items' => $this->of($menu, $locale)] : ['style' => null, 'items' => []];
    }

    /** @return list<array<string, mixed>> */
    public function of(Menu $menu, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $version = Cache::get(self::VERSION_KEY, 0);

        return Cache::rememberForever("menu:{$menu->id}:{$version}:{$locale}", fn (): array => $this->build($menu->items()->get(), null, $locale, $menu->style));
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
    protected function build(Collection $items, ?int $parentId, string $locale, string $style = 'dropdown'): array
    {
        return $items->where('parent_id', $parentId)
            ->filter(fn (MenuItem $item): bool => $this->links->isVisible($item))   // a hidden parent hides its children
            ->map(fn (MenuItem $item): array => [
                'title' => $this->links->label($item, $locale),
                'url' => $this->links->href($item, $locale),
                'new_tab' => $item->open_in_new_tab,
                'icon' => $item->icon,
                'class' => $item->css_class,
                ...($parentId === null ? $this->panel($item, $style, $locale) : []),
                'children' => $this->build($items, $item->id, $locale, $style),
            ])
            ->values()->all();
    }

    /** Top item: plain dropdown, or a mega panel with its columns, picture, text and "view all". */
    protected function panel(MenuItem $item, string $style, string $locale): array
    {
        if (! $item->isMega($style)) {
            return ['mega' => false];
        }

        $options = (array) $item->options;
        $pick = fn (?array $texts): ?string => $texts ? ($texts[$locale] ?? collect($texts)->first()) : null;
        $image = isset($options['image']) ? MediaItem::query()->where('is_active', true)->find($options['image']) : null;

        return [
            'mega' => true,
            'columns' => (int) ($options['columns'] ?? 4),
            'image' => $image?->url('medium'),
            'image_alt' => $image ? (string) $image->alt_text : null,
            'description' => $pick($options['description'] ?? null),
            'cta' => filled($options['cta_url'] ?? null) ? ['label' => $pick($options['cta_label'] ?? null), 'url' => $options['cta_url']] : null,
        ];
    }
}
