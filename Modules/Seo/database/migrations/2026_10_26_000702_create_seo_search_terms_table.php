<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-02 v0.4 §9.4 seo_search_terms — L: what visitors search on the website and how many results it gave the last time,
 * so the office sees what people look for and what they do not find (Phase 4.7g). One row a search (lower case, squished).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_search_terms', function (Blueprint $table) {
            $table->id();
            $table->string('term', 191);
            $table->char('term_hash', 64)->unique();
            $table->unsignedInteger('hits')->default(0);
            $table->unsignedInteger('last_results')->default(0);
            $table->timestamp('first_searched_at')->nullable();
            $table->timestamp('last_searched_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_search_terms');
    }
};
