<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-01 §8.2–8.4 — Person Assignment Engine. */
return new class extends Migration
{
    public function up(): void
    {
        // B: people who are in no directory (ADR-14); a built-in person source
        Schema::create('core_manual_persons', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('designation')->nullable();
            $table->json('organization')->nullable();
            $table->string('email', 191)->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('profile_url')->nullable();
            $table->platformColumns();
        });

        // C: Standard Role Catalog — fixed keys, editable labels (FR-REL-11)
        Schema::create('core_assignment_roles', function (Blueprint $table) {
            $table->id();
            $table->string('assignable_type', 100);
            $table->string('role_key', 50);
            $table->json('label');
            $table->boolean('is_single_holder')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['assignable_type', 'role_key']);
        });

        // B (P-02): person ↔ assignable with role, order, term
        Schema::create('core_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('person_type', 100);
            $table->unsignedBigInteger('person_id');
            $table->string('assignable_type', 100);
            $table->unsignedBigInteger('assignable_id');
            $table->string('role_key', 50)->index();
            $table->json('role_label')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable()->index();
            $table->boolean('is_public')->default(true);
            $table->json('overrides')->nullable();   // {phone, extension, email}
            $table->json('note')->nullable();
            $table->platformColumns();

            $table->index(['assignable_type', 'assignable_id', 'role_key', 'sort_order'], 'core_assignments_target_index');
            $table->index(['person_type', 'person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_assignments');
        Schema::dropIfExists('core_assignment_roles');
        Schema::dropIfExists('core_manual_persons');
    }
};
