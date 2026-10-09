<?php

namespace Modules\Office;

use Modules\Office\Filament\OfficePlugin;
use Modules\Office\Models\Office;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

final class OfficeExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(OfficePlugin::class);
        $r->relationTarget(Office::class);                        // content can be shown in an office
        $r->assignable(Office::class, roles: ['head', 'team']);   // Standard Role Catalog (DB-01 §8.4)
        $r->dynamicPermissions(Office::class);                    // "Manage:Office#{id}" per office (FR-ROLE-07)
    }
}
