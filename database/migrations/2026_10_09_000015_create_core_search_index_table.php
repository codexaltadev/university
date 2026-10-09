<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-02 §10: C-17 Global Search (P-16). One row per record and language; only
 * website-visible records (FR-SRCH-03). owner_key = the record's owner entity
 * ("department.department:12"), for "only in this department" (FR-SRCH-06).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_search_index', function (Blueprint $table) {
            $table->id();
            $table->string('searchable_type', 100);
            $table->unsignedBigInteger('searchable_id');
            $table->string('locale', 10);
            $table->string('module_slug', 100);
            $table->string('owner_key', 150)->nullable()->index();
            $table->string('title', 500);
            $table->mediumText('body')->nullable();
            $table->string('url', 2048);
            $table->unsignedTinyInteger('weight')->default(5);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['searchable_type', 'searchable_id', 'locale'], 'core_search_index_record_unique');
            $table->index(['locale', 'module_slug']);
            $table->fullText(['title', 'body']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_search_index');
    }
};
