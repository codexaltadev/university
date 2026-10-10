<?php

namespace Modules\Scholarship\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Required default data only, no demo content; must be safe to run twice.
 * FR-ROLE-04 v1.1: the Waiver Office manages scholarships too. Given once — when the role
 * exists (Waiver module) and has no scholarship permission yet; an Admin may give them to
 * any other role and take them away, a second run leaves that alone.
 */
class ScholarshipDatabaseSeeder extends Seeder
{
    public const ROLE = 'Waiver Office';

    /** @var list<string> */
    public const PERMISSIONS = [
        'ViewAny:Scholarship', 'View:Scholarship', 'Create:Scholarship', 'Update:Scholarship',
        'Delete:Scholarship', 'DeleteAny:Scholarship', 'Restore:Scholarship', 'RestoreAny:Scholarship',
        'ForceDelete:Scholarship', 'ForceDeleteAny:Scholarship', 'Reorder:Scholarship', 'Replicate:Scholarship',
    ];

    public function run(): void
    {
        $role = Role::query()->where('name', self::ROLE)->where('guard_name', 'web')->first();
        if ($role === null || $role->permissions()->where('name', 'like', '%:Scholarship')->exists()) {
            return;
        }

        $role->givePermissionTo(Permission::query()->where('guard_name', 'web')->whereIn('name', self::PERMISSIONS)->get());
    }
}
