<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-02 §13: C-06 Theme (kernel engine). Design tokens and module layouts live in
 * core_settings (groups "theme.{slug}" and "layouts").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_themes', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 100);
            $table->string('version', 30);
            $table->string('source_path', 255);                                // themes/{slug} or the runtime folder (ADR-12)
            $table->json('manifest');                                          // theme.json snapshot
            $table->boolean('is_active')->default(false)->index();              // one active (application rule)
            $table->boolean('is_bundled')->default(false);                      // BRL-THEME-01
            $table->foreignId('installed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_themes');
    }
};
