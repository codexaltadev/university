<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-05 §3 v1.2: news posts (B, attachable). Where a post shows is core_relations ("Show in" and
 * the main site); "featured" is that place's highlight. active_at schedules it (no published_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_posts', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('subtitle')->nullable();
            $table->json('excerpt')->nullable();
            $table->json('body')->nullable();
            $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('author_label')->nullable();
            $table->string('video_url', 2048)->nullable();
            $table->timestamp('active_at')->nullable()->index();
            $table->platformColumns();

            $table->index(['is_active', 'deleted_at', 'active_at']);   // IX-5
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_posts');
    }
};
