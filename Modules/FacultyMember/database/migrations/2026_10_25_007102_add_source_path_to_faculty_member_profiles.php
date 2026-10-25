<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The teacher's address on DIU's Faculty Management System — "{faculty}/{department}/{teacher}"
 * (FBE/IE/muntachir) — for their full profile, publications, CV and vCard from its API.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faculty_member_profiles', function (Blueprint $table): void {
            $table->string('source_path', 191)->nullable()->after('external_ref');
        });
    }

    public function down(): void
    {
        Schema::table('faculty_member_profiles', function (Blueprint $table): void {
            $table->dropColumn('source_path');
        });
    }
};
