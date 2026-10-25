<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-02 v0.3: quick_links — the links a page with a sidebar shows under its menu as "Quick Links"
 * ([{label: {en, bn}, url}], LinkListField; read with link_list()). DIU's /admission has them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_pages', function (Blueprint $table) {
            $table->json('quick_links')->nullable()->after('sidebar_menu_id');
        });
    }

    public function down(): void
    {
        Schema::table('page_pages', function (Blueprint $table) {
            $table->dropColumn('quick_links');
        });
    }
};
