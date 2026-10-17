<?php

namespace Modules\Facilities;

use Modules\Facilities\Filament\FacilitiesPlugin;
use Modules\Facilities\Models\Facility;
use Modules\Facilities\Sections\FacilitiesSection;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class FacilitiesExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(FacilitiesPlugin::class);                                                     // Services › Facilities
        $r->attachable(Facility::class);                                                                 // FR-FCL-01, FR-FCL-05
        $r->vocabulary(Facility::CATEGORY, label: 'Facility category', multiple: false);
        $r->homepageSection(FacilitiesSection::class);                                                   // FR-FCL-04
        $r->websitePage('facilities.index', label: 'facilities::facilities.plural', path: '/'.Facility::PATH);
        $r->searchable(Facility::class, label: 'facilities::facilities.plural', weight: 4);
        $r->sitemap(Facility::class);
        $r->layouts('facilities.listing', ['icons', 'cards', 'list']);                                  // FR-FCL-02
    }
}
