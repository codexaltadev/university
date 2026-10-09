<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-03 §11.2 (v1.1): boards, waiver categories (+ includes), waiver rules and their
 * conditions. No row in a condition pivot = the rule applies to all (BRL-FEE-04).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_term_id')->constrained('core_terms')->restrictOnDelete();
            $table->json('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();
        });

        Schema::create('fee_waiver_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_term_id')->constrained('core_terms')->restrictOnDelete();
            $table->json('name');
            $table->boolean('uses_diploma_result')->default(false);           // v1.1: HSC field = Diploma CGPA (0–4)
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();
        });

        Schema::create('fee_waiver_category_includes', function (Blueprint $table) {   // v1.1
            $table->foreignId('category_id')->constrained('fee_waiver_categories')->cascadeOnDelete();
            $table->foreignId('included_category_id')->constrained('fee_waiver_categories')->cascadeOnDelete();
            $table->primary(['category_id', 'included_category_id']);
            $table->index('included_category_id');
        });

        Schema::create('fee_waiver_rules', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->foreignId('level_term_id')->constrained('core_terms')->restrictOnDelete();
            $table->foreignId('fee_category_id')->nullable()->constrained('fee_categories')->restrictOnDelete();
            $table->decimal('ssc_min', 3, 2)->nullable();
            $table->decimal('ssc_max', 3, 2)->nullable();
            $table->decimal('hsc_min', 3, 2)->nullable();
            $table->decimal('hsc_max', 3, 2)->nullable();
            $table->boolean('require_ssc_golden')->default(false);
            $table->boolean('require_hsc_golden')->default(false);
            $table->decimal('ug_cgpa_min', 3, 2)->nullable();
            $table->decimal('ug_cgpa_max', 3, 2)->nullable();
            $table->boolean('is_international')->nullable();
            $table->decimal('waiver_percent', 5, 2);
            $table->decimal('improved_waiver_percent', 5, 2)->nullable();      // v1.1: when HSC GPA > SSC GPA
            $table->decimal('max_waiver_percent', 5, 2)->nullable();           // cap in "additive" mode
            $table->json('message')->nullable();
            $table->smallInteger('priority')->default(0);
            $table->platformColumns();

            $table->index(['level_term_id', 'fee_category_id', 'is_active']);
        });

        Schema::create('fee_waiver_rule_boards', function (Blueprint $table) {
            $table->foreignId('rule_id')->constrained('fee_waiver_rules')->cascadeOnDelete();
            $table->foreignId('board_id')->constrained('fee_boards')->cascadeOnDelete();
            $table->primary(['rule_id', 'board_id']);
            $table->index('board_id');
        });

        Schema::create('fee_waiver_rule_categories', function (Blueprint $table) {
            $table->foreignId('rule_id')->constrained('fee_waiver_rules')->cascadeOnDelete();
            $table->foreignId('waiver_category_id')->constrained('fee_waiver_categories')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);            // v1.1: the first one names the result
            $table->primary(['rule_id', 'waiver_category_id']);
            $table->index('waiver_category_id');
        });

        Schema::create('fee_waiver_rule_genders', function (Blueprint $table) {
            $table->foreignId('rule_id')->constrained('fee_waiver_rules')->cascadeOnDelete();
            $table->string('gender', 10);                                       // male / female / other
            $table->primary(['rule_id', 'gender']);
        });

        Schema::create('fee_waiver_rule_faculties', function (Blueprint $table) {
            $table->foreignId('rule_id')->constrained('fee_waiver_rules')->cascadeOnDelete();
            $table->foreignId('faculty_id')->constrained('faculty_faculties')->cascadeOnDelete();
            $table->primary(['rule_id', 'faculty_id']);
            $table->index('faculty_id');
        });

        Schema::create('fee_waiver_rule_tuitions', function (Blueprint $table) {
            $table->foreignId('rule_id')->constrained('fee_waiver_rules')->cascadeOnDelete();
            $table->foreignId('tuition_id')->constrained('fee_tuitions')->cascadeOnDelete();
            $table->primary(['rule_id', 'tuition_id']);
            $table->index('tuition_id');
        });
    }

    public function down(): void
    {
        foreach (['fee_waiver_rule_tuitions', 'fee_waiver_rule_faculties', 'fee_waiver_rule_genders', 'fee_waiver_rule_categories', 'fee_waiver_rule_boards', 'fee_waiver_rules', 'fee_waiver_category_includes', 'fee_waiver_categories', 'fee_boards'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
