<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-02 §5: menu_menus (P-04) and menu_items (P-11). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_menus', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('owner_type', 100)->default('site');             // main site = ("site", 0)
            $table->unsignedBigInteger('owner_id')->default(0);
            $table->string('location', 50);                                   // primary, topbar, footer-1 …
            $table->platformColumns();

            $table->unique(['owner_type', 'owner_id', 'location', 'active_key'], 'menu_menus_location_unique');   // BRL-MENU-03
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menu_menus')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->string('type', 20);                                       // record, module_page, taxonomy, url, file, heading
            $table->string('linkable_type', 100)->nullable();
            $table->unsignedBigInteger('linkable_id')->nullable();
            $table->string('route_key', 150)->nullable();
            $table->text('url')->nullable();
            $table->json('title')->nullable();                                // null: the linked record's title
            $table->string('icon', 100)->nullable();
            $table->boolean('open_in_new_tab')->default(false);
            $table->string('css_class', 100)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->index(['menu_id', 'parent_id', 'sort_order']);
            $table->index(['linkable_type', 'linkable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menu_menus');
    }
};
