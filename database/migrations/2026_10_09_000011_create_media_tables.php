<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-02 §3: C-07 Media Library (kernel-backed: HasMediaUsage and MediaPicker use these tables). */
return new class extends Migration
{
    public function up(): void
    {
        // B: nested folders; ("site", 0) = everyone's (P-22)
        Schema::create('core_media_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('core_media_folders')->restrictOnDelete();
            $table->string('name', 191);
            $table->string('scope_type', 100)->default('site');
            $table->unsignedBigInteger('scope_id')->default(0);
            $table->platformColumns();

            $table->index(['scope_type', 'scope_id']);
            $table->unique(['parent_id', 'scope_type', 'scope_id', 'name', 'active_key'], 'core_media_folders_name_unique');
        });

        // B: one uploaded file = one row here + one spatie "media" row (collection "file")
        Schema::create('core_media_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->nullable()->constrained('core_media_folders')->nullOnDelete();
            $table->json('title')->nullable();
            $table->json('alt_text')->nullable();
            $table->json('caption')->nullable();
            $table->json('description')->nullable();
            $table->string('kind', 20);                         // image, document, video, audio, archive, other
            $table->string('mime_type', 127);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->char('file_hash', 64)->index();             // duplicate warning (FR-MEDIA-16)
            $table->string('scope_type', 100)->default('site'); // uploader's scope (BRL-MEDIA-01)
            $table->unsignedBigInteger('scope_id')->default(0);
            $table->boolean('is_shared')->default(false);
            $table->boolean('is_private')->default(false);      // signed URL only (BRL-MEDIA-02)
            $table->unsignedInteger('usage_count')->default(0)->index();
            $table->platformColumns();

            $table->index(['scope_type', 'scope_id']);
            $table->index(['kind', 'created_at']);
        });

        // S (F-07): spatie/laravel-medialibrary, unchanged
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->uuid()->nullable()->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable()->index();
            $table->nullableTimestamps();
        });

        // P (P-13): where a media item is used
        Schema::create('core_media_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_item_id')->constrained('core_media_items')->cascadeOnDelete();
            $table->string('usable_type', 100);
            $table->unsignedBigInteger('usable_id');
            $table->string('field', 60);                        // featured_image, gallery, logo …
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['usable_type', 'usable_id', 'field', 'media_item_id'], 'core_media_usages_unique');
            $table->index(['usable_type', 'usable_id', 'field', 'sort_order'], 'core_media_usages_field_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_media_usages');
        Schema::dropIfExists('media');
        Schema::dropIfExists('core_media_items');
        Schema::dropIfExists('core_media_folders');
    }
};
