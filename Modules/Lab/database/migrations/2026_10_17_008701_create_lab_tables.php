<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-06 v1.3 §3: labs / research centres (microsite entity, relation target, assignable) and their
 * items — equipment, scopes / services, student projects, products — in one table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_labs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id')->nullable()->index();   // Department is optional: no foreign key
            $table->json('name');
            $table->string('short_name', 50)->nullable();
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->json('location')->nullable();
            $table->json('summary')->nullable();
            $table->json('body')->nullable();
            $table->json('equipment_summary')->nullable();
            $table->string('email', 191)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('video_url', 2048)->nullable();
            $table->string('website_url', 2048)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();
        });

        Schema::create('lab_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_id')->constrained('lab_labs')->cascadeOnDelete();
            $table->string('item_type', 20);
            $table->json('title');
            $table->json('description')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->json('student_names')->nullable();
            $table->string('student_ids', 255)->nullable();
            $table->json('options')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->index(['lab_id', 'item_type', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_items');
        Schema::dropIfExists('lab_labs');
    }
};
