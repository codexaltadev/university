<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-03 §11.3 v1.4: scholarships (B), one level of parts — a scholarship (DIU Scholarship,
 * Erasmus …) and the calls / opportunities under it. Shown when is_active; no publish date.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarship_scholarships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('scholarship_scholarships')->restrictOnDelete();   // a parent with parts is not removed
            $table->json('title');
            $table->json('summary')->nullable();
            $table->json('details')->nullable();
            $table->json('eligibility')->nullable();
            $table->json('how_to_apply')->nullable();
            $table->string('apply_url', 2048)->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->index(['parent_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarship_scholarships');
    }
};
