<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-06 v1.3 §4: facilities (attachable; icon, picture and gallery from the Media Library). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facilities_facilities', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('summary')->nullable();
            $table->json('body')->nullable();
            $table->string('external_url', 2048)->nullable();
            $table->boolean('open_in_new_tab')->default(false);
            $table->unsignedBigInteger('campus_id')->nullable()->index();   // Campus is optional: no FK
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facilities_facilities');
    }
};
