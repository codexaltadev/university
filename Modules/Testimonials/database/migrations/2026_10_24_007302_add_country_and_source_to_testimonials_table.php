<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-05 §6 v1.12: country — where the writer is from (ISO 3166 two letters; empty = Bangladesh),
 * so the website can tell local and international voices apart; source_key — the record a
 * testimonial was made from ("international.visitor:12": a visitor's comment shown as a
 * testimonial too, owner 2026-10-24), one testimonial per source.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials_testimonials', function (Blueprint $table) {
            $table->char('country', 2)->nullable()->after('testimonial_type');
            $table->string('source_key', 100)->nullable()->unique()->after('video_url');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials_testimonials', function (Blueprint $table) {
            $table->dropUnique(['source_key']);
            $table->dropColumn(['country', 'source_key']);
        });
    }
};
