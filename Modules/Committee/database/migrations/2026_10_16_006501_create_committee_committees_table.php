<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-04 §7 v1.4: committees (B). Members are core assignments (assignable), where a committee
 * shows is core relations ("Show in", main site); a new term is a new committee pointing at
 * the previous one. Shown when is_active; no publish date.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committee_committees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_term_id')->nullable()->constrained('core_terms')->nullOnDelete();
            $table->json('name');
            $table->json('description')->nullable();
            $table->date('formed_on')->nullable();
            $table->date('term_starts_on')->nullable();
            $table->date('term_ends_on')->nullable();
            $table->foreignId('previous_term_id')->nullable()->constrained('committee_committees')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->index(['category_term_id', 'sort_order']);
            $table->index('previous_term_id');
            $table->index('term_ends_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_committees');
    }
};
