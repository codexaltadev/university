<?php

namespace Modules\Accreditation;

use Modules\Accreditation\Filament\AccreditationPlugin;
use Modules\Accreditation\Models\Accreditation;
use Modules\Accreditation\Models\Ranking;
use Modules\Accreditation\Notifications\AccreditationExpiring;
use Modules\Accreditation\Sections\RecognitionSection;
use Modules\Accreditation\Settings\AccreditationSettings;
use Modules\Accreditation\Tabs\ProgramAccreditationTab;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class AccreditationExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(AccreditationPlugin::class);                                                  // Services › Accreditations, Rankings
        $r->attachable(Accreditation::class);                                                            // FR-ACR-01: a program, department, faculty
        $r->attachable(Ranking::class);
        $r->vocabulary(Accreditation::CATEGORY, label: 'Accreditation category', multiple: false);
        $r->homepageSection(RecognitionSection::class);                                                  // FR-ACR-06
        $r->websitePage('accreditation.index', label: 'accreditation::accreditation.plural', path: '/'.Accreditation::PATH);
        $r->websitePage('accreditation.rankings', label: 'accreditation::accreditation.rankings', path: '/'.Ranking::PATH);
        $r->searchable(Accreditation::class, label: 'accreditation::accreditation.plural', weight: 3);
        $r->sitemap(Accreditation::class);
        $r->settings(AccreditationSettings::class);                                                      // FR-ACR-02: hide or mark the expired
        $r->notification(AccreditationExpiring::class);                                                  // FR-ACR-02: 60 days before

        $program = 'Modules\\Program\\Models\\Program';
        if (class_exists($program)) {
            $r->entityTab($program, ProgramAccreditationTab::class);                                     // FR-ACR-05; Program is optional
        }
    }
}
