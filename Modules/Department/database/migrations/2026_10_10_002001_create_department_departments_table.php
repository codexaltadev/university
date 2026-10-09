<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-03 §3: M-02 Department — department_departments (B). Media: photo, cover; Head = assignment "head". */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('faculty_faculties')->restrictOnDelete();   // FR-DEPT-01
            $table->json('name');
            $table->string('short_name', 30);                                   // e.g. CSE
            $table->json('about_title')->nullable();
            $table->json('about')->nullable();
            $table->json('head_message')->nullable();
            $table->string('external_url', 2048)->nullable();                   // FR-DEPT-04
            $table->string('email', 191)->nullable();
            $table->string('phone', 50)->nullable();
            $table->json('location')->nullable();
            $table->unsignedBigInteger('campus_id')->nullable()->index();       // Campus is optional: no FK
            $table->boolean('show_in_listing')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->unique(['faculty_id', 'short_name', 'active_key']);         // BRL-DEPT-01
            $table->index(['faculty_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_departments');
    }
};
