<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DB-02 v0.2 §5.1: a menu with no location is a free menu — a page picks it as its own
 * sidebar menu. The unique (owner, location) key lets an owner have any number of them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_menus', function (Blueprint $table) {
            $table->string('location', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('menu_menus')->whereNull('location')->delete();

        Schema::table('menu_menus', function (Blueprint $table) {
            $table->string('location', 50)->nullable(false)->change();
        });
    }
};
