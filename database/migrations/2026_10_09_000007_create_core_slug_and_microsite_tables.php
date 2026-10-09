<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-01 §9 (core_slugs, ADR-11) and §10 (core_microsite_settings, P-09). */
return new class extends Migration
{
    public function up(): void
    {
        // S (P-21): translatable slugs with history for 301 redirects
        Schema::create('core_slugs', function (Blueprint $table) {
            $table->id();
            $table->string('sluggable_type', 100);
            $table->unsignedBigInteger('sluggable_id');
            $table->string('locale', 10);
            $table->string('slug', 191);
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->unique(['sluggable_type', 'locale', 'slug']);   // IX-9, history included (FR-GEN-31)
            $table->index(['sluggable_type', 'sluggable_id', 'locale', 'is_current'], 'core_slugs_record_index');
        });

        // C (P-09): per-microsite template and fallback modes
        Schema::create('core_microsite_settings', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type', 100);
            $table->unsignedBigInteger('owner_id');
            $table->string('template', 100)->nullable();
            $table->json('fallback_modes')->nullable();   // {"menu": "inherit", "footer": "main", ...}
            $table->json('options')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_microsite_settings');
        Schema::dropIfExists('core_slugs');
    }
};
