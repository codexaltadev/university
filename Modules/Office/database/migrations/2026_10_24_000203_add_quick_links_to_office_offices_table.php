<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-04 v1.6: quick_links — the links an office page shows under "Quick Links", entered with the
 * office ([{label: {en, bn}, url}], LinkListField; read with link_list()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('office_offices', function (Blueprint $table) {
            $table->json('quick_links')->nullable()->after('extension');
        });
    }

    public function down(): void
    {
        Schema::table('office_offices', function (Blueprint $table) {
            $table->dropColumn('quick_links');
        });
    }
};
