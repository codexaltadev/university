<?php

namespace Modules\Leadership;

use Modules\Leadership\Filament\LeadershipPlugin;
use Modules\Leadership\Models\LeadershipTerm;
use Modules\Leadership\Models\Position;
use Modules\Leadership\Sections\LeadershipMessageSection;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class LeadershipExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->assignable(Position::class, roles: ['holder', 'member']);                            // single holder (VC) or many (Trustees)
        $r->filamentPlugin(LeadershipPlugin::class);                                            // People › Leadership
        $r->vocabulary(Position::GROUP, label: 'Leadership group', multiple: false);            // Board of Trustees, Executive … (FR-LEAD-02)
        $r->websitePage('leadership.index', label: 'leadership::leadership.plural', path: '/'.LeadershipTerm::PATH);
        $r->websitePage('leadership.former', label: 'leadership::leadership.former', path: '/'.LeadershipTerm::PATH.'/former');
        $r->searchable(LeadershipTerm::class, label: 'leadership::leadership.plural', weight: 7);
        $r->sitemap(LeadershipTerm::class);
        $r->layouts('leadership.listing', ['grid', 'cards', 'list']);                           // FR-LEAD-03; first = default
        $r->homepageSection(LeadershipMessageSection::class);                                   // FR-LEAD-06
    }
}
