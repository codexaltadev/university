<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-03 §2: M-01 Faculty — faculty_faculties (B). Media: photo, cover, logo; Dean = assignment "dean". */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculty_faculties', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('short_name', 30);                                   // e.g. FSIT
            $table->json('overview')->nullable();
            $table->json('dean_message')->nullable();
            $table->string('email', 191)->nullable();
            $table->string('phone', 50)->nullable();
            $table->json('location')->nullable();
            $table->unsignedBigInteger('campus_id')->nullable()->index();       // Campus is optional: no FK
            $table->boolean('show_in_listing')->default(true);                  // FR-FAC-09
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->unique(['short_name', 'active_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_faculties');
    }
};
