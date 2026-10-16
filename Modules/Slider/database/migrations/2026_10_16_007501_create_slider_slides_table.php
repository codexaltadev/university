<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-05 §8 v1.7: hero slides and page banners (attachable — one slide can show in many places).
 * Pictures and the background video are Media Library items (image_desktop, image_mobile, video).
 * No publish date: is_active, active_at (from), expires_at (until).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slider_slides', function (Blueprint $table) {
            $table->id();
            $table->string('placement', 20)->default('hero')->index();
            $table->json('title')->nullable();
            $table->json('subtitle')->nullable();
            $table->json('buttons')->nullable();
            $table->json('bullets')->nullable();
            $table->string('text_position', 10)->default('left');
            $table->unsignedTinyInteger('overlay_opacity')->default(40);
            $table->boolean('video_on_mobile')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('active_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->platformColumns();

            $table->index(['placement', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slider_slides');
    }
};
