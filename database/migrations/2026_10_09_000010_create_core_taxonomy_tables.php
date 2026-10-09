<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-02 §4: C-16 Taxonomy (kernel-backed: the HasTaxonomy trait uses these tables). */
return new class extends Migration
{
    public function up(): void
    {
        // C: one row per registered vocabulary, kept in sync from the modules' Extensions
        Schema::create('core_vocabularies', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();             // news.category, shared.tags
            $table->string('module_slug', 100)->nullable()->index();   // null = shared
            $table->json('name');
            $table->boolean('is_hierarchical')->default(false);
            $table->boolean('allows_multiple')->default(true);
            $table->boolean('is_ordered')->default(false);
            $table->timestamps();
        });

        // B: categories and tags
        Schema::create('core_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vocabulary_id')->constrained('core_vocabularies')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('core_terms')->restrictOnDelete();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('color', 20)->nullable();
            $table->string('icon', 100)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->index(['vocabulary_id', 'parent_id', 'sort_order']);
        });

        // P (P-12): term ↔ any content
        Schema::create('core_termables', function (Blueprint $table) {
            $table->foreignId('term_id')->constrained('core_terms')->cascadeOnDelete();
            $table->string('termable_type', 100);
            $table->unsignedBigInteger('termable_id');
            $table->unsignedSmallInteger('sort_order')->default(0);   // primary category first
            $table->timestamp('created_at')->nullable();

            $table->primary(['term_id', 'termable_type', 'termable_id']);
            $table->index(['termable_type', 'termable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_termables');
        Schema::dropIfExists('core_terms');
        Schema::dropIfExists('core_vocabularies');
    }
};
