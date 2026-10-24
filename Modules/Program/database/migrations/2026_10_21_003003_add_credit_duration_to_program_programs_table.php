<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-03 v1.8 §4.1: credit_duration — the "Credit & Duration" text of the program page
 * (DIU's program_credit_duration), beside the numbers taken from it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_programs', function (Blueprint $table) {
            $table->json('credit_duration')->nullable()->after('objective');
        });
    }

    public function down(): void
    {
        Schema::table('program_programs', function (Blueprint $table) {
            $table->dropColumn('credit_duration');
        });
    }
};
