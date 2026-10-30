<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-04 v1.7: what an office page shows near its top, entered with the office —
 * highlights (cards such as Mission, Vision: [{icon, title: {en, bn}, text: {en, bn}}]) and
 * quick_services (icon tiles: [{icon, label: {en, bn}, url}]), both in order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('office_offices', function (Blueprint $table) {
            $table->json('highlights')->nullable()->after('quick_links');
            $table->json('quick_services')->nullable()->after('highlights');
        });
    }

    public function down(): void
    {
        Schema::table('office_offices', function (Blueprint $table) {
            $table->dropColumn(['highlights', 'quick_services']);
        });
    }
};
