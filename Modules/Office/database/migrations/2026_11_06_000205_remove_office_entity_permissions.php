<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Office users now work through data scope (BRD Q-13 v1.9): the "Manage:Office#{id}" permissions go.
 * Labs keep theirs.
 */
return new class extends Migration
{
    public function up(): void
    {
        $ids = DB::table('permissions')->where('name', 'like', 'Manage:Office#%')->pluck('id');

        DB::table('core_entity_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Nothing to bring back: the permissions were per record and no longer used
    }
};
