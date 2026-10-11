<?php

namespace Platform\Theme;

/**
 * A menu shown at a location instead of the owner's for the page being drawn (DB-02 v0.2
 * §8.1): a page's own sidebar menu — Admission's section pages, DLRC, Medical … The page
 * module sets it while it renders; the menu module reads it. Lives for one request.
 *
 *   app(LocationMenus::class)->use('sidebar', 12);
 *   app(LocationMenus::class)->for('sidebar');   // 12, or null: the owner's menu at that location
 */
class LocationMenus
{
    /** @var array<string, int> */
    protected array $menus = [];

    public function use(string $location, ?int $menuId): void
    {
        if ($menuId === null) {
            unset($this->menus[$location]);

            return;
        }

        $this->menus[$location] = $menuId;
    }

    public function for(string $location): ?int
    {
        return $this->menus[$location] ?? null;
    }
}
