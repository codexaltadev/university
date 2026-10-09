<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-02 §7: homepage_layouts — C (P-06) and homepage_sections — B. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_layouts', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type', 100)->default('site');             // main site = ("site", 0)
            $table->unsignedBigInteger('owner_id')->default(0);
            $table->boolean('is_active')->default(true);                       // inactive → theme default (FR-HOME-08)
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['owner_type', 'owner_id']);
        });

        Schema::create('homepage_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layout_id')->constrained('homepage_layouts')->cascadeOnDelete();
            $table->string('section_type', 100)->index();                     // core.hero, news.latest …
            $table->json('heading')->nullable();
            $table->json('subheading')->nullable();
            $table->json('settings')->nullable();                             // source mode, count, layout, background …
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('visible_from')->nullable();                    // FR-HOME-06
            $table->timestamp('visible_until')->nullable();
            $table->platformColumns();

            $table->index(['layout_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_sections');
        Schema::dropIfExists('homepage_layouts');
    }
};
