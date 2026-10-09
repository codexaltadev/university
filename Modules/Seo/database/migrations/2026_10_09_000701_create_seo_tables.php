<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-02 §9.2 seo_redirects — B (P-15) and §9.3 seo_not_found_logs — L. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('source_path', 2048);                               // normalized, without query
            $table->char('source_hash', 64);                                   // SHA-256 of source_path
            $table->string('destination_url', 2048)->nullable();               // external or a path
            $table->string('target_type', 100)->nullable();                     // or an internal record (P-15)
            $table->unsignedBigInteger('target_id')->nullable();
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->string('source', 20)->default('manual');                  // manual, import, 404, slug
            $table->platformColumns();

            $table->unique(['source_hash', 'active_key']);
            $table->index(['target_type', 'target_id']);
        });

        Schema::create('seo_not_found_logs', function (Blueprint $table) {
            $table->id();
            $table->string('path', 2048);
            $table->char('path_hash', 64)->unique();
            $table->unsignedInteger('hits')->default(0);
            $table->string('last_referrer', 2048)->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->boolean('is_resolved')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_not_found_logs');
        Schema::dropIfExists('seo_redirects');
    }
};
