<?php

namespace Platform\Theme;

use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;

/**
 * Menu tree for theme views (Theme Guide §7.2): each item has label, url, target,
 * icon, class, type (link | heading), isActive, children; top items of a mega menu
 * also mega, columns, image, description, cta.
 */
final class MenuItems
{
    /**
     * @param  list<array<string, mixed>>  $items  as the Menu module gives them
     * @return Collection<int, Fluent>
     */
    public static function from(array $items, int $depth = 3, ?string $current = null): Collection
    {
        $current ??= rtrim(request()->url(), '/');

        return collect($items)->map(function (array $item) use ($depth, $current): Fluent {
            $children = $depth > 1 ? self::from((array) ($item['children'] ?? []), $depth - 1, $current) : collect();
            $url = $item['url'] ?? null;

            return new Fluent([
                ...$item,
                'label' => $item['title'] ?? '',
                'url' => $url,
                'target' => ! empty($item['new_tab']) ? '_blank' : null,
                'type' => $url === null ? 'heading' : 'link',
                'isActive' => ($url !== null && rtrim(url($url), '/') === $current) || $children->contains(fn (Fluent $c): bool => $c->isActive),   // "/admission" too
                'children' => $children,
            ]);
        });
    }
}
