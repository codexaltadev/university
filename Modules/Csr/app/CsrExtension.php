<?php

namespace Modules\Csr;

use Modules\Csr\Filament\CsrPlugin;
use Modules\Csr\Models\Activity;
use Modules\Csr\Sections\CsrSection;
use Modules\Csr\Settings\CsrSettings;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class CsrExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(CsrPlugin::class);                                                            // Services › CSR
        $r->attachable(Activity::class);                                                                 // FR-CSR-01, FR-CSR-04
        $r->vocabulary(Activity::CATEGORY, label: 'CSR category', hierarchical: true, multiple: false);
        $r->homepageSection(CsrSection::class);                                                          // FR-CSR-03
        $r->websitePage('csr.index', label: 'csr::csr.plural', path: '/'.Activity::PATH);
        $r->searchable(Activity::class, label: 'csr::csr.plural', weight: 3);
        $r->sitemap(Activity::class);
        $r->layouts('csr.listing', ['cards', 'grid', 'timeline']);                                     // FR-CSR-02
        $r->settings(CsrSettings::class);                                                                // the page's texts, numbers, report
    }
}
