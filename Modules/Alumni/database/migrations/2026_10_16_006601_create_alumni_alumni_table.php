<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-04 §8 v1.5: alumni (B, person source). Shown when is_active — a visitor's submission comes
 * in off until the Alumni Office checks it. Contacts are hidden unless switched on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni_alumni', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('student_id', 30)->nullable();
            $table->unsignedBigInteger('department_id')->nullable();   // Department / Program are optional: no FK
            $table->unsignedBigInteger('program_id')->nullable()->index();
            $table->string('batch', 30)->nullable()->index();
            $table->unsignedSmallInteger('passing_year')->nullable();
            $table->json('current_designation')->nullable();
            $table->json('organization')->nullable();
            $table->string('job_type', 20)->nullable()->index();
            $table->json('office_address')->nullable();
            $table->string('email', 191)->nullable();
            $table->string('phone', 50)->nullable();
            $table->boolean('show_email')->default(false);
            $table->boolean('show_phone')->default(false);
            $table->boolean('show_address')->default(false);
            $table->json('social_links')->nullable();
            $table->string('video_url', 2048)->nullable();
            $table->json('story')->nullable();
            $table->boolean('is_pride')->default(false)->index();
            $table->string('submission_source', 20)->default('admin');
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->unique(['student_id', 'active_key']);
            $table->index(['department_id', 'passing_year']);
            $table->index(['submission_source', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_alumni');
    }
};
