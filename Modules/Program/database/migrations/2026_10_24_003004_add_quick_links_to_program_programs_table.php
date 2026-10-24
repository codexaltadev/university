<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-03 v1.9: quick_links — the links its page shows under "Quick Links", entered with the
 * Program itself ([{label: {en, bn}, url}], LinkListField; read with link_list()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_programs', function (Blueprint $table) {
            $table->json('quick_links')->nullable()->after('video_url');
        });
    }

    public function down(): void
    {
        Schema::table('program_programs', function (Blueprint $table) {
            $table->dropColumn('quick_links');
        });
    }
};
