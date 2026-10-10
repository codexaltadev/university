<?php

namespace Modules\Waiver\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Required default data only, no demo content; must be safe to run twice.
 * The module's default role (FR-ROLE-04 v1.1): "Waiver Office" — waiver rules, waiver categories, boards and the calculator settings; programs and tuitions read only. Made once; an Admin
 * may change it in the role editor afterwards (a second run leaves it alone).
 */
class WaiverDatabaseSeeder extends Seeder
{
    public const ROLE = 'Waiver Office';

    /** @var list<string> */
    public const PERMISSIONS = [
        'ViewAny:WaiverRule',
        'View:WaiverRule',
        'Create:WaiverRule',
        'Update:WaiverRule',
        'Delete:WaiverRule',
        'DeleteAny:WaiverRule',
        'Restore:WaiverRule',
        'RestoreAny:WaiverRule',
        'ForceDelete:WaiverRule',
        'ForceDeleteAny:WaiverRule',
        'Reorder:WaiverRule',
        'Replicate:WaiverRule',
        'ViewAny:WaiverCategory',
        'View:WaiverCategory',
        'Create:WaiverCategory',
        'Update:WaiverCategory',
        'Delete:WaiverCategory',
        'DeleteAny:WaiverCategory',
        'Restore:WaiverCategory',
        'RestoreAny:WaiverCategory',
        'ForceDelete:WaiverCategory',
        'ForceDeleteAny:WaiverCategory',
        'Reorder:WaiverCategory',
        'Replicate:WaiverCategory',
        'ViewAny:Board',
        'View:Board',
        'Create:Board',
        'Update:Board',
        'Delete:Board',
        'DeleteAny:Board',
        'Restore:Board',
        'RestoreAny:Board',
        'ForceDelete:Board',
        'ForceDeleteAny:Board',
        'Reorder:Board',
        'Replicate:Board',
        'Manage:WaiverSettings',
        'ViewAny:Tuition',
        'View:Tuition',
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
