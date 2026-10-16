<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-04 §6 v1.3: leadership positions (B, assignable) in a group, and the terms of the people
 * who hold them (B) — who and when live in core_assignments, the message and biography here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leadership_positions', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->foreignId('group_term_id')->nullable()->constrained('core_terms')->nullOnDelete();
            $table->boolean('is_single_holder')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->index(['group_term_id', 'sort_order']);
        });

        Schema::create('leadership_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained('leadership_positions')->cascadeOnDelete();
            $table->foreignId('assignment_id')->unique()->constrained('core_assignments')->cascadeOnDelete();
            $table->json('message')->nullable();
            $table->json('biography')->nullable();
            $table->platformColumns();

            $table->index('position_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leadership_terms');
        Schema::dropIfExists('leadership_positions');
    }
};
