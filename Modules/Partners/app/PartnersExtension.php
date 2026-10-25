<?php

namespace Modules\Partners;

use Modules\Partners\Filament\PartnersPlugin;
use Modules\Partners\Models\Partner;
use Modules\Partners\Sections\PartnersSection;
use Modules\Partners\Settings\PartnersSettings;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class PartnersExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(PartnersPlugin::class);                                                       // Services › Partners
        $r->attachable(Partner::class);                                                                  // FR-PRT-03
        $r->vocabulary(Partner::CATEGORY, label: 'Discount category', multiple: false);
        $r->homepageSection(PartnersSection::class);                                                     // FR-PRT-05
        $r->websitePage('partners.index', label: 'partners::partners.plural', path: '/'.Partner::PATH);
        $r->websitePage('partners.discounts', label: 'partners::partners.discounts', path: '/'.Partner::DISCOUNT_PATH);
        $r->settings(PartnersSettings::class);                                                           // the pages' titles, lines, Quick Links
    }
}
