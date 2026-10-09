<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-03 §11.1 (v1.1): fee categories, tuitions and their lines. Totals are worked out
 * from the lines unless overridden; tuition_* is the part a waiver applies to.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_categories', function (Blueprint $table) {          // FR-FEE-01
            $table->id();
            $table->json('name');                                                // "Local", "International"
            $table->string('code', 20);
            $table->char('currency', 3)->default('BDT');
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->unique(['code', 'active_key']);
        });

        Schema::create('fee_tuitions', function (Blueprint $table) {            // FR-FEE-02/12
            $table->id();
            $table->foreignId('program_id')->constrained('program_programs')->restrictOnDelete();
            $table->foreignId('fee_category_id')->constrained('fee_categories')->restrictOnDelete();
            $table->unsignedBigInteger('session_id')->nullable()->index();      // Session is optional: no FK
            $table->json('label')->nullable();                                   // v1.1: "1 Year" / "2 Years" of one program
            $table->decimal('credit_min', 6, 1)->nullable();
            $table->decimal('credit_max', 6, 1)->nullable();
            $table->unsignedTinyInteger('total_terms')->nullable();
            $table->json('duration_label')->nullable();
            $table->decimal('tuition_min', 12, 2)->nullable();                   // v1.1: waiver base
            $table->decimal('tuition_max', 12, 2)->nullable();
            $table->decimal('total_min', 12, 2)->nullable();
            $table->decimal('total_max', 12, 2)->nullable();
            $table->boolean('totals_overridden')->default(false);
            $table->string('prospectus_url', 2048)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->index(['program_id', 'fee_category_id', 'session_id']);
        });

        Schema::create('fee_tuition_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tuition_id')->constrained('fee_tuitions')->cascadeOnDelete();
            $table->string('line_type', 20);                                     // admission / year / term / per_credit / other
            $table->json('label');
            $table->decimal('amount', 12, 2);
            $table->decimal('amount_max', 12, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->index(['tuition_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_tuition_lines');
        Schema::dropIfExists('fee_tuitions');
        Schema::dropIfExists('fee_categories');
    }
};
