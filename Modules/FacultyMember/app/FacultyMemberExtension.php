<?php

namespace Modules\FacultyMember;

use Modules\FacultyMember\Filament\FacultyMemberPlugin;
use Modules\FacultyMember\Metrics\FacultyMemberCount;
use Modules\FacultyMember\Models\FacultyMember;
use Modules\FacultyMember\Sections\FacultyMembersSection;
use Modules\FacultyMember\Settings\FacultyMemberSettings;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class FacultyMemberExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->personSource(FacultyMember::class);                                                  // Dean, Head, committees … pick teachers (FR-FM-10)
        $r->filamentPlugin(FacultyMemberPlugin::class);                                         // People › Faculty Members (read only + Sync now)
        $r->scopeable(FacultyMember::class);                                                    // Department users: their own teachers
        $r->vocabulary(FacultyMember::DESIGNATION, label: 'Designation', multiple: false);      // ordered ladder (FR-FM-06)
        $r->metric('faculty-member.count', FacultyMemberCount::class);                          // FR-FM-12
        $r->websitePage('faculty-member.index', label: 'facultymember::facultymember.plural', path: '/'.FacultyMember::PATH);   // also /{department}/faculty-members
        $r->searchable(FacultyMember::class, label: 'facultymember::facultymember.plural', weight: 5);
        $r->menuItemType(FacultyMember::class);
        $r->layouts('faculty-member.directory', ['grid', 'list', 'cards']);                     // FR-FM-03; first = default
        $r->homepageSection(FacultyMembersSection::class);                                      // FR-FM-05, FR-DEPT-07
        $r->settings(FacultyMemberSettings::class);                                             // source, daily sync, faculty website
    }
}
