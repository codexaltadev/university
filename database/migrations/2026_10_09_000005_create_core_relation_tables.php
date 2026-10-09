<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-01 §8.1, §8.1.1 — Content Relation Engine (C-13). */
return new class extends Migration
{
    public function up(): void
    {
        // P-01: content ↔ target; ("site", 0) = published on the main site
        Schema::create('core_relations', function (Blueprint $table) {
            $table->id();
            $table->string('content_type', 100);
            $table->unsignedBigInteger('content_id');
            $table->string('target_type', 100);
            $table->unsignedBigInteger('target_id');
            $table->boolean('is_highlighted')->default(false);
            $table->unsignedSmallInteger('highlight_order')->default(0);
            $table->timestamp('highlight_until')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->platformPivotColumns();

            $table->unique(['content_type', 'content_id', 'target_type', 'target_id'], 'core_relations_unique');
            $table->index(['target_type', 'target_id', 'content_type'], 'core_relations_target_index');
            $table->index(['target_type', 'target_id', 'content_type', 'is_highlighted', 'highlight_order'], 'core_relations_highlight_index');
            $table->index(['is_highlighted', 'highlight_until'], 'core_relations_expiry_index');
        });

        // P-23: entity users ask for main-site publishing; Admin decides (FR-CNT-H09)
        Schema::create('core_main_site_requests', function (Blueprint $table) {
            $table->id();
            $table->string('content_type', 100);
            $table->unsignedBigInteger('content_id');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at');
            $table->string('decision', 10)->default('pending');   // pending / published / dismissed
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();

            $table->index(['content_type', 'content_id']);
            $table->index(['decision', 'requested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_main_site_requests');
        Schema::dropIfExists('core_relations');
    }
};
