<?php

namespace Modules\Role\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Default roles (User Manual Part C §14, FR-ROLE-04). Permissions are ticked by a
 * Super Admin in the role editor; Super Admin itself is a protected system role.
 * Safe to run again: existing roles keep their permissions.
 */
class RoleDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            config('filament-shield.super_admin.name') => ['Everything, everywhere. IT / web team lead only.', true],
            'admin' => ['Central web team: all content and settings, main site; not System.', false],
            'content_manager' => ['Selected content modules only.', false],
            'entity_editor' => ['Faculty / department / program editors; limited by "Works on".', false],
            'office_editor' => ['Office / lab editors; limited by entity permissions.', false],
            'viewer' => ['Can look, cannot change.', false],
        ];

        foreach ($roles as $name => [$description, $system]) {
            $role = Role::findOrCreate($name, config('auth.defaults.guard'));
            $role->forceFill(['description' => $role->description ?? $description, 'is_system' => $system])->save();
        }
    }
}
