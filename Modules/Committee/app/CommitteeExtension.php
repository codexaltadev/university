<?php

namespace Modules\Committee;

use Modules\Committee\Filament\CommitteePlugin;
use Modules\Committee\Models\Committee;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class CommitteeExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(CommitteePlugin::class);                                            // People › Committees
        $r->attachable(Committee::class);                                                      // "Show in" entities, main site (FR-COM-04)
        $r->assignable(Committee::class, roles: Committee::ROLES);                             // members (FR-COM-02/03), catalog order
        $r->vocabulary(Committee::CATEGORY, label: 'Committee category', multiple: false);    // Statutory, Academic, Administrative … (FR-COM-01)
        $r->websitePage('committee.index', label: 'committee::committee.plural', path: '/'.Committee::PATH);   // also /{entity}/committees
        $r->searchable(Committee::class, label: 'committee::committee.plural', weight: 5);
        $r->sitemap(Committee::class);
        $r->menuItemType(Committee::class);
        $r->layouts('committee.listing', ['table', 'cards', 'list']);                          // FR-COM-06; first = default
    }
}
