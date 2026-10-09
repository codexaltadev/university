<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-01 §5.1–5.2 — module / kind on permissions, system roles, entity permission map. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('module_slug', 100)->nullable()->index()->after('guard_name');   // FR-ROLE-02/03
            $table->string('kind', 20)->default('custom')->after('module_slug');            // resource / page / widget / custom / entity
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->string('description', 255)->nullable()->after('guard_name');
            $table->boolean('is_system')->default(false)->after('description');             // FR-ROLE-04
        });

        // P-10: which entity a dynamic permission ("Manage:Office#12") belongs to
        Schema::create('core_entity_permissions', function (Blueprint $table) {
            $table->foreignId('permission_id')->primary()->constrained('permissions')->cascadeOnDelete();
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');
            $table->string('action', 30);
            $table->platformPivotColumns();

            $table->unique(['entity_type', 'entity_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_entity_permissions');

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['description', 'is_system']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropIndex(['module_slug']);
            $table->dropColumn(['module_slug', 'kind']);
        });
    }
};
