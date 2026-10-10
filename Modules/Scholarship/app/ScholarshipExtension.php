<?php

namespace Modules\Scholarship;

use Modules\Scholarship\Filament\ScholarshipPlugin;
use Modules\Scholarship\Models\Scholarship;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class ScholarshipExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(ScholarshipPlugin::class);                                          // Academic › Scholarships
        $r->searchable(Scholarship::class, label: 'scholarship::scholarship.plural', weight: 7);   // website search (FR-SRCH-01)
        $r->sitemap(Scholarship::class);                                                       // sitemap.xml (FR-SEO-02)
        $r->menuItemType(Scholarship::class);                                                  // menus can link a scholarship
        $r->vocabulary(Scholarship::CATEGORY, label: 'Scholarship category', hierarchical: true, multiple: false);   // Government, International … (DB-03 §11.3)
    }
}
