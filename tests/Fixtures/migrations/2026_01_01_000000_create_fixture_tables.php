<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Test-only tables for kernel behaviour (loaded only when running tests). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixture_parents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->platformColumns();
        });

        Schema::create('fixture_children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('fixture_parents')->restrictOnDelete();
            $table->string('name');
            $table->string('code', 30);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->platformColumns();

            $table->unique(['code', 'active_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixture_children');
        Schema::dropIfExists('fixture_parents');
    }
};
