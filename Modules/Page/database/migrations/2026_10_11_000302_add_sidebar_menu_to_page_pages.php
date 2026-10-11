<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-02 v0.2 §8.1: a page's own sidebar menu (a free menu of the same owner); child pages
 * without one use their parent's, then the owner's menu at the "sidebar" location.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_pages', function (Blueprint $table) {
            // another module's table (Menu): an index, not a foreign key (SAD Part A §6.6); a removed menu = none
            $table->unsignedBigInteger('sidebar_menu_id')->nullable()->after('template')->index();
        });
    }

    public function down(): void
    {
        Schema::table('page_pages', function (Blueprint $table) {
            $table->dropIndex(['sidebar_menu_id']);
            $table->dropColumn('sidebar_menu_id');
        });
    }
};
