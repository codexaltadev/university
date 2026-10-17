<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-03 §8 v1.7: a read-only copy of the teachers kept on the university's faculty site.
 * `faculty-member:sync` writes the source's columns; only the contact switches and the
 * order are this site's own. Profile sections are one JSON column (always replaced whole).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculty_member_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('external_ref', 100);
            $table->foreignId('department_id')->nullable()->constrained('department_departments')->nullOnDelete();
            $table->foreignId('designation_term_id')->nullable()->constrained('core_terms')->nullOnDelete();
            $table->json('designation_note')->nullable();
            $table->string('employee_id', 30)->nullable();
            $table->json('name');
            $table->string('email', 191)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('extension', 20)->nullable();
            $table->string('office_room', 100)->nullable();
            $table->string('photo_url', 500)->nullable();
            $table->string('employment_type', 20)->default('full_time')->index();
            $table->json('biography')->nullable();
            $table->json('research_interests')->nullable();
            $table->json('links')->nullable();
            $table->json('profile_items')->nullable();
            $table->char('source_hash', 40)->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->boolean('show_email')->default(true);
            $table->boolean('show_phone')->default(true);
            $table->boolean('show_extension')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->unique(['external_ref', 'active_key']);
            $table->unique(['employee_id', 'active_key']);
            $table->index(['department_id', 'is_active', 'designation_term_id', 'sort_order'], 'faculty_member_profiles_directory_index');
        });

        Schema::create('faculty_member_departments', function (Blueprint $table) {
            $table->foreignId('profile_id')->constrained('faculty_member_profiles')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('department_departments')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['profile_id', 'department_id']);
            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_member_departments');
        Schema::dropIfExists('faculty_member_profiles');
    }
};
