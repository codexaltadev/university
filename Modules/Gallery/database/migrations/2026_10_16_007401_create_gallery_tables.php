<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-05 §7 v1.6: albums (attachable; their pictures and uploaded videos are Media Library
 * items used in field "items" — no item table, no copies) and the link videos of an album
 * (YouTube / Vimeo / Facebook — not files). No publish date: is_active, active_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gallery_albums', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('description')->nullable();
            $table->string('series_type', 20)->nullable()->index();
            $table->unsignedBigInteger('event_id')->nullable()->index();   // Events is optional: no FK
            $table->date('occurred_on')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('active_at')->nullable();
            $table->platformColumns();

            $table->index(['is_active', 'active_at']);
        });

        Schema::create('gallery_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained('gallery_albums')->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('provider', 20);
            $table->string('provider_id', 100)->nullable()->index();   // the same video in two albums
            $table->json('title')->nullable();
            $table->unsignedSmallInteger('episode_no')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->index(['album_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_videos');
        Schema::dropIfExists('gallery_albums');
    }
};
