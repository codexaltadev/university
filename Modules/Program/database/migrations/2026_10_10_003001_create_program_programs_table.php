<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-03 §4.1: M-03 Program — program_programs (B). Media: image, brochure. Level = a term
 * of program.level (column); tags = program.tag (core_termables). Counters such as approved
 * alumni live in the Program's stat set, not in columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('department_departments')->restrictOnDelete();   // FR-PROG-01
            $table->foreignId('level_term_id')->constrained('core_terms')->restrictOnDelete();               // FR-PROG-03
            $table->json('name');
            $table->string('short_name', 50);
            $table->json('subtitle')->nullable();
            $table->json('degree_name')->nullable();
            $table->decimal('total_credits', 6, 1)->nullable();
            $table->decimal('duration_years', 3, 1)->nullable();
            $table->unsignedTinyInteger('total_terms')->nullable();
            $table->json('duration_label')->nullable();
            $table->string('medium', 30)->nullable();
            $table->string('shift', 20)->nullable()->index();              // day / evening / weekend
            $table->string('class_mode', 20)->nullable()->index();         // on_campus / online / hybrid
            $table->json('summary')->nullable();
            $table->json('objective')->nullable();
            $table->json('section_intros')->nullable();
            $table->json('ai_summary')->nullable();
            $table->json('ai_short_summary')->nullable();
            $table->json('eligibility_general')->nullable();                // FR-PROG-10
            $table->json('eligibility_english_medium')->nullable();
            $table->json('eligibility_rules')->nullable();
            $table->date('next_intake_on')->nullable();
            $table->string('application_url', 2048)->nullable();
            $table->string('video_url', 2048)->nullable();
            $table->boolean('show_in_listing')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable()->index();
            $table->platformColumns();

            $table->index(['department_id', 'level_term_id', 'is_active']);
            $table->index(['is_active', 'deleted_at', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_programs');
    }
};
