<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-04 v1.8 §4.2 (owner 2026-11-01): how a member is employed — regular, probation, contractual,
 * part time, outsourced. Only regular staff are in the directory; the others are people offices,
 * committees and labs tag (Member::EMPLOYMENT).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administration_members', function (Blueprint $table) {
            $table->string('employment_type', 20)->default('regular')->after('employee_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('administration_members', function (Blueprint $table) {
            $table->dropIndex(['employment_type']);
            $table->dropColumn('employment_type');
        });
    }
};
