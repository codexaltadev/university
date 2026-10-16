<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-04 §4 v1.2: one ordered list of categories (group, public) and one table of members —
 * everyone but teachers. Members are a person source: offices, committees, admission contacts
 * … point at them through core_assignments, so a phone number lives in one place.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administration_categories', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('group', 20)->index();   // management / officer / support
            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->platformColumns();
        });

        Schema::create('administration_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('administration_categories')->restrictOnDelete();
            $table->string('employee_id', 30)->nullable();
            $table->json('name');
            $table->json('designation')->nullable();
            $table->unsignedBigInteger('primary_office_id')->nullable();   // Office is optional: no FK
            $table->string('email', 191)->nullable()->index();
            $table->string('phone', 50)->nullable();
            $table->string('extension', 20)->nullable();
            $table->json('biography')->nullable();
            $table->boolean('show_email')->default(true);
            $table->boolean('show_phone')->default(true);
            $table->boolean('show_extension')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->unique(['employee_id', 'active_key']);
            $table->index(['category_id', 'is_active', 'sort_order']);
            $table->index(['primary_office_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administration_members');
        Schema::dropIfExists('administration_categories');
    }
};
