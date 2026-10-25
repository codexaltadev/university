<?php

namespace Modules\Alumni;

use Modules\Alumni\Filament\AlumniPlugin;
use Modules\Alumni\Models\Alumnus;
use Modules\Alumni\Sections\PrideAlumniSection;
use Modules\Alumni\Settings\AlumniSettings;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class AlumniExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->personSource(Alumnus::class);                                                      // committees, events … pick alumni
        $r->filamentPlugin(AlumniPlugin::class);                                              // People › Alumni
        $r->scopeable(Alumnus::class);                                                        // Department / Program users
        $r->websitePage('alumni.index', label: 'alumni::alumni.plural', path: '/'.Alumnus::PATH);   // also /{department|program}/alumni
        $r->websitePage('alumni.submit', label: 'alumni::alumni.submit.title', path: '/'.Alumnus::PATH.'/submit');
        $r->searchable(Alumnus::class, label: 'alumni::alumni.plural', weight: 4);
        $r->sitemap(Alumnus::class);
        $r->menuItemType(Alumnus::class);
        $r->layouts('alumni.directory', ['grid', 'cards', 'list']);                           // FR-ALUM-02; first = default
        $r->homepageSection(PrideAlumniSection::class);                                       // FR-ALUM-04
        $r->settings(AlumniSettings::class);                                                  // the page's title, text, picture, portal link
    }
}
