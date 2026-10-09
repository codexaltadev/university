<?php

namespace Modules\Faculty;

use Modules\Faculty\Filament\FacultyPlugin;
use Modules\Faculty\Metrics\FacultyCount;
use Modules\Faculty\Models\Faculty;
use Modules\Faculty\Sections\OurFacultiesSection;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class FacultyExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(FacultyPlugin::class);
        $r->relationTarget(Faculty::class);                       // top of the hierarchy (FR-FAC-02)
        $r->scopeable(Faculty::class);                            // Faculty users
        $r->micrositeEntity(Faculty::class, routePrefix: 'faculty', home: 'faculty::microsite.home');   // /faculty/{slug}
        $r->assignable(Faculty::class, roles: ['dean', 'associate_dean', 'officer', 'contact']);        // FR-FAC-03
        $r->metric('faculty.count', FacultyCount::class);
        $r->layouts('faculty.listing', ['grid', 'list', 'card']);  // FR-FAC-07
        $r->homepageSection(OurFacultiesSection::class);           // FR-FAC-08
        $r->searchable(Faculty::class, label: 'faculty::faculty.plural', weight: 8);
        $r->sitemap(Faculty::class);
    }
}
