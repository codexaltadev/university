<?php

namespace Modules\International;

use Modules\International\Filament\InternationalPlugin;
use Modules\International\Metrics\CountriesCount;
use Modules\International\Metrics\PartnersCount;
use Modules\International\Models\Conference;
use Modules\International\Models\Institution;
use Modules\International\Models\Visitor;
use Modules\International\Notifications\AgreementExpiring;
use Modules\International\Sections\GlobalPartnersSection;
use Modules\International\Settings\InternationalSettings;
use Modules\International\Support\InternationalWebsite;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class InternationalExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(InternationalPlugin::class);                                                  // International › Partners, Conferences, Visitors, Countries
        $r->attachable(Institution::class);                                                              // FR-INTL-01/04
        $r->attachable(Conference::class);                                                               // FR-INTL-05
        $r->attachable(Visitor::class);                                                                  // FR-INTL-06
        $r->vocabulary(Conference::CATEGORY, label: 'Conference category', multiple: false);
        $r->homepageSection(GlobalPartnersSection::class);                                               // FR-INTL-09
        $r->websitePage('international.index', label: 'international::international.title', path: '/'.InternationalWebsite::ROOT);
        $r->websitePage('international.partners', label: 'international::international.partners', path: '/'.InternationalWebsite::ROOT.'/partners');
        $r->websitePage('international.conferences', label: 'international::international.conferences', path: '/'.Conference::PATH);
        $r->websitePage('international.visitors', label: 'international::international.visitors', path: '/'.Visitor::PATH);
        $r->searchable(Institution::class, label: 'international::international.partners', weight: 2);
        $r->searchable(Conference::class, label: 'international::international.conferences', weight: 2);
        $r->sitemap(Conference::class);
        $r->metric('international.partners', PartnersCount::class);                                     // FR-INTL-08
        $r->metric('international.countries', CountriesCount::class);
        $r->settings(InternationalSettings::class);                                                      // FR-INTL-03
        $r->notification(AgreementExpiring::class);
        $r->layouts('international.partners', InternationalWebsite::VIEWS);                             // FR-INTL-02
    }
}
