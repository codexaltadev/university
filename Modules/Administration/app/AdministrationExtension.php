<?php

namespace Modules\Administration;

use Modules\Administration\Filament\AdministrationPlugin;
use Modules\Administration\Models\Member;
use Modules\Administration\Settings\AdministrationSettings;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class AdministrationExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->personSource(Member::class);                                                        // offices, committees … pick members (FR-REL-09)
        $r->filamentPlugin(AdministrationPlugin::class);                                       // People › Administration, Categories
        $r->scopeable(Member::class);                                                          // Office users: their office's members
        $r->websitePage('administration.index', label: 'administration::administration.plural', path: '/'.Member::PATH);
        $r->searchable(Member::class, label: 'administration::administration.plural', weight: 5);
        $r->sitemap(Member::class);
        $r->menuItemType(Member::class);
        $r->layouts('administration.directory', ['grid', 'list', 'table']);                    // FR-ADMN-04; first = default
        $r->settings(AdministrationSettings::class);                                           // profile pages, intro
    }
}
