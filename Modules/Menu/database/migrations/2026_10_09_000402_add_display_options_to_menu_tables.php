<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-02 §5 (v1.1): how a menu opens on the website — dropdown, multi-level dropdown
 * or mega menu — and per top item the mega panel (columns, picture, text, "view all").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_menus', function (Blueprint $table) {
            $table->string('style', 20)->default('dropdown')->after('location');   // dropdown, multilevel, mega
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->json('options')->nullable()->after('css_class');               // display, columns, image, description, cta
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', fn (Blueprint $table) => $table->dropColumn('options'));
        Schema::table('menu_menus', fn (Blueprint $table) => $table->dropColumn('style'));
    }
};
