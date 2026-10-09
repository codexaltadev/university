<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-03 §4.2: Program sub-items (all B), edited inside the Program (BRL-PROG-04).
 * program_id cascades: a force-deleted Program takes its sub-items along.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_majors', function (Blueprint $table) {             // FR-PROG-04
            $table->id();
            $table->foreignId('program_id')->constrained('program_programs')->cascadeOnDelete();
            $table->foreignId('category_term_id')->nullable()->constrained('core_terms')->nullOnDelete();   // program.major-category
            $table->json('title');
            $table->json('short_description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();
        });

        Schema::create('program_list_items', function (Blueprint $table) {         // FR-PROG-05
            $table->id();
            $table->foreignId('program_id')->constrained('program_programs')->cascadeOnDelete();
            $table->string('list_type', 20);                                        // career_path / industry
            $table->json('title');
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->index(['program_id', 'list_type', 'sort_order']);
        });

        Schema::create('program_facilitators', function (Blueprint $table) {       // FR-PROG-06; media photo
            $table->id();
            $table->foreignId('program_id')->constrained('program_programs')->cascadeOnDelete();
            $table->string('name', 191);
            $table->json('designation')->nullable();
            $table->json('organization')->nullable();
            $table->string('type', 30)->nullable();
            $table->string('profile_url', 2048)->nullable();
            $table->string('linkedin_url', 2048)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('phone', 50)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();
        });

        Schema::create('program_materials', function (Blueprint $table) {          // FR-PROG-07; media file
            $table->id();
            $table->foreignId('program_id')->constrained('program_programs')->cascadeOnDelete();
            $table->json('title');
            $table->string('url', 2048)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();
        });

        Schema::create('program_memberships', function (Blueprint $table) {        // FR-PROG-08; media logo
            $table->id();
            $table->foreignId('program_id')->constrained('program_programs')->cascadeOnDelete();
            $table->json('title');
            $table->json('short_description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();
        });

        Schema::create('program_variants', function (Blueprint $table) {           // FR-PROG-09; media image
            $table->id();
            $table->foreignId('program_id')->constrained('program_programs')->cascadeOnDelete();
            $table->foreignId('related_program_id')->nullable()->constrained('program_programs')->nullOnDelete();
            $table->json('title');
            $table->json('short_description');
            $table->json('details')->nullable();
            $table->json('button_text')->nullable();
            $table->string('button_url', 2048)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();
        });
    }

    public function down(): void
    {
        foreach (['program_variants', 'program_memberships', 'program_materials', 'program_facilitators', 'program_list_items', 'program_majors'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
