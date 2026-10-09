<?php

namespace Modules\Office;

use Modules\Office\Filament\OfficePlugin;
use Modules\Office\Metrics\OfficeCount;
use Modules\Office\Models\Office;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

final class OfficeExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(OfficePlugin::class);
        $r->metric('office.count', OfficeCount::class);
        $r->relationTarget(Office::class);                        // content can be shown in an office
        $r->micrositeEntity(Office::class, routePrefix: 'office'); // /office/{slug}; no parent: office → main site
        $r->assignable(Office::class, roles: ['head', 'team']);   // Standard Role Catalog (DB-01 §8.4)
        $r->dynamicPermissions(Office::class);                    // "Manage:Office#{id}" per office (FR-ROLE-07)
        $r->searchable(Office::class, label: 'office::office.search_group', weight: 7);   // website search (FR-SRCH-01)
    }
}
