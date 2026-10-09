<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-02 §9.1: C-12 per-record SEO (P-14, FR-GEN-70). Empty fields are filled from
 * the record on the website (FR-GEN-71). OG image = Media usage (core.seo-meta, og_image).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_seo_meta', function (Blueprint $table) {
            $table->id();
            $table->string('seoable_type', 100);
            $table->unsignedBigInteger('seoable_id');
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->string('canonical_url', 2048)->nullable();
            $table->boolean('noindex')->default(false);
            $table->json('schema_overrides')->nullable();
            $table->timestamps();

            $table->unique(['seoable_type', 'seoable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_seo_meta');
    }
};
