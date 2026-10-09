<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-01 §7 (core_settings, C-05) and §11 (core_languages, core_translations, C-15). */
return new class extends Migration
{
    public function up(): void
    {
        // C: system configuration, no soft delete / is_active (BRL-SET-01)
        Schema::create('core_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 100);                   // general, contact … or a module slug (FR-SET-10)
            $table->string('key', 150);
            $table->json('value')->nullable();              // translatable: {"en": "...", "bn": "..."}
            $table->boolean('is_translatable')->default(false);
            $table->boolean('is_encrypted')->default(false);
            $table->boolean('is_public')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['group', 'key']);
        });

        Schema::create('core_languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();           // URL prefix and JSON key
            $table->string('name', 50);
            $table->string('native_name', 50);
            $table->string('direction', 3)->default('ltr');
            $table->boolean('is_default')->default(false);  // exactly one, kept by the application (BRL-LANG-01)
            $table->boolean('is_active')->default(true);
            $table->boolean('use_native_digits')->default(false);   // FR-LANG-08
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Admin overrides of interface strings; lang/ files stay the defaults (FR-LANG-03)
        Schema::create('core_translations', function (Blueprint $table) {
            $table->id();
            $table->string('namespace', 100);               // "*" (core / theme) or a module's namespace
            $table->string('group', 100);                   // "frontend"
            $table->string('key', 191);
            $table->json('text');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['namespace', 'group', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_translations');
        Schema::dropIfExists('core_languages');
        Schema::dropIfExists('core_settings');
    }
};
