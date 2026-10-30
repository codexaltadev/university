<?php

namespace Modules\Office;

use Modules\Office\Blocks\OfficeContactBlock;
use Modules\Office\Blocks\OfficeHeadsBlock;
use Modules\Office\Filament\OfficePlugin;
use Modules\Office\Metrics\OfficeCount;
use Modules\Office\Metrics\OfficeServiceCount;
use Modules\Office\Metrics\OfficeTeamCount;
use Modules\Office\Models\Office;
use Modules\Office\Models\OfficeSection;
use Modules\Office\Models\OfficeService;
use Modules\Office\Models\OfficeSupportContact;
use Modules\Office\Settings\OfficeSettings;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

final class OfficeExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(OfficePlugin::class);
        $r->metric('office.count', OfficeCount::class);
        $r->metric('office.services', OfficeServiceCount::class);   // FR-OFF-14
        $r->metric('office.team', OfficeTeamCount::class);
        $r->relationTarget(Office::class);                        // content can be shown in an office
        $r->micrositeEntity(Office::class, routePrefix: 'office', home: 'office::microsite.home'); // /office/{slug}; no parent: office → main site
        $r->assignable(Office::class, roles: ['head', 'team']);   // Standard Role Catalog (DB-01 §8.4)
        $r->assignable(OfficeSection::class, roles: [OfficeSection::ROLE]);                 // FR-OFF-04
        $r->assignable(OfficeService::class, roles: [OfficeService::ROLE]);                 // FR-OFF-06
        $r->assignable(OfficeSupportContact::class, roles: [OfficeSupportContact::ROLE]);   // FR-OFF-07
        $r->dynamicPermissions(Office::class);                    // "Manage:Office#{id}" per office (FR-ROLE-07)
        $r->searchable(Office::class, label: 'office::office.search_group', weight: 7);   // website search (FR-SRCH-01)
        $r->searchable(OfficeService::class, label: 'office::office.services.plural', weight: 5);
        $r->vocabulary(Office::TYPE, label: 'Office type', multiple: false);   // Administrative, Student Service … (FR-OFF-01)
        $r->layouts('office.listing', ['grid', 'list', 'directory']);          // Appearance › Layouts; first = default
        $r->websitePage('office.index', label: 'office::office.plural', path: '/'.Office::LISTING);   // Menus › Module page
        $r->websitePage('office.services', label: 'office::office.services.plural', path: '/services');
        $r->websitePage('office.support', label: 'office::office.support.plural', path: '/support-contacts');
        $r->settings(OfficeSettings::class);                      // default Quick Links of office pages
        $r->pageBlock(OfficeHeadsBlock::class);                   // Honour Board on an office's page (DIU registrar-office)
        $r->pageBlock(OfficeContactBlock::class);                 // Contact on an office's page
    }
}
