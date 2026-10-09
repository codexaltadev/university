<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-02 §8.1: page_pages — B (P-08). Slug in core_slugs, files in core_media_usages. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_pages', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type', 100)->default('site');             // main site = ("site", 0), never NULL
            $table->unsignedBigInteger('owner_id')->default(0);
            $table->foreignId('parent_id')->nullable()->constrained('page_pages')->restrictOnDelete();
            $table->json('title');
            $table->json('excerpt')->nullable();
            $table->json('content')->nullable();                             // per language: list of blocks
            $table->string('template', 50)->default('default');
            $table->timestamp('published_at')->nullable();                    // HasPublishDate
            $table->timestamp('expires_at')->nullable();                      // HasExpiry
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->index(['owner_type', 'owner_id', 'parent_id', 'sort_order'], 'page_pages_owner_index');
            $table->index(['is_active', 'deleted_at', 'published_at']);       // IX-5
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_pages');
    }
};
