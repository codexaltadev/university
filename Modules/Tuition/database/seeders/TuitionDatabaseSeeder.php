<?php

namespace Modules\Tuition\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Required default data only, no demo content; must be safe to run twice.
 * The module's default role (FR-ROLE-04 v1.1): "Admission Office" — tuition fees and tuition categories, the programs read only. Made once; an Admin
 * may change it in the role editor afterwards (a second run leaves it alone).
 */
class TuitionDatabaseSeeder extends Seeder
{
    public const ROLE = 'Admission Office';

    /** @var list<string> */
    public const PERMISSIONS = [
        'ViewAny:Tuition',
        'View:Tuition',
        'Create:Tuition',
        'Update:Tuition',
        'Delete:Tuition',
        'DeleteAny:Tuition',
        'Restore:Tuition',
        'RestoreAny:Tuition',
        'ForceDelete:Tuition',
        'ForceDeleteAny:Tuition',
        'Reorder:Tuition',
        'Replicate:Tuition',
        'ViewAny:FeeCategory',
        'View:FeeCategory',
        'Create:FeeCategory',
        'Update:FeeCategory',
        'Delete:FeeCategory',
        'DeleteAny:FeeCategory',
        'Restore:FeeCategory',
        'RestoreAny:FeeCategory',
        'ForceDelete:FeeCategory',
        'ForceDeleteAny:FeeCategory',
        'Reorder:FeeCategory',
        'Replicate:FeeCategory',
        'ViewAny:Program',
        'View:Program',
    ];

    public function run(): void
    {
        if (Role::query()->where('name', self::ROLE)->where('guard_name', 'web')->exists()) {
            return;
        }

        $role = Role::create(['name' => self::ROLE, 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::query()->where('guard_name', 'web')->whereIn('name', self::PERMISSIONS)->get());
    }
}
