<?php

namespace Modules\Page;

use Modules\Page\Filament\PagePlugin;
use Modules\Page\Models\Page;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class PageExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(PagePlugin::class);
        $r->menuItemType(Page::class);   // menus can link pages
        $r->sitemap(Page::class);        // sitemap.xml (FR-SEO-02)
        $r->searchable(Page::class, label: 'page::page.search_group', weight: 6);   // website search (FR-SRCH-01)
    }
}
