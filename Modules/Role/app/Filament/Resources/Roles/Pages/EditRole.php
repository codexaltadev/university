<?php

namespace Modules\Role\Filament\Resources\Roles\Pages;

use BezhanSalleh\FilamentShield\Resources\Roles\Pages\EditRole as ShieldEditRole;
use Modules\Role\Filament\Resources\Roles\RoleResource;
use Platform\Modules\ModulePermissions;

class EditRole extends ShieldEditRole
{
    protected static string $resource = RoleResource::class;

    /**
     * Shield syncs only the permissions shown in the form. Inactive modules are
     * not in the panel, so keep the role's existing permissions of those modules.
     */
    protected function afterSave(): void
    {
        $inactive = collect(app(ModulePermissions::class)->inactiveModulePermissions())->flatten();

        $kept = $this->record->permissions()
            ->pluck('name')
            ->intersect($inactive);

        $this->permissions = $this->permissions->merge($kept)->unique()->values();

        parent::afterSave();
    }
}
