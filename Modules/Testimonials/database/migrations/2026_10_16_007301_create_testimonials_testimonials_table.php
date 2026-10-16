<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-05 §6 v1.5: testimonials (B, attachable; the person is an assignment, role "author").
 * Shown when is_active, in the dragged order; no publish date.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials_testimonials', function (Blueprint $table) {
            $table->id();
            $table->json('quote');
            $table->string('testimonial_type', 20)->index();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('video_url', 2048)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials_testimonials');
    }
};
