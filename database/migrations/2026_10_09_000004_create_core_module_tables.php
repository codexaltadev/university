<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-01 §3 — Module Manager (C-01). */
return new class extends Migration
{
    public function up(): void
    {
        // System (S): module status — storage of the database activator (ADR-02)
        Schema::create('core_modules', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 100)->unique();
            $table->string('type', 20)->index();                   // core / installable
            $table->string('version', 30);
            $table->string('status', 20)->default('installed')->index();   // installed / active / failed / uninstalled
            $table->boolean('is_bundled')->default(false);
            $table->string('source_path', 255);
            $table->json('manifest');
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->foreignId('installed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Log (L): version history
        Schema::create('core_module_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('core_modules')->cascadeOnDelete();
            $table->string('from_version', 30)->nullable();
            $table->string('to_version', 30);
            $table->string('backup_path', 255)->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable()->index();
        });

        // Log (L): step-by-step operation log (FR-MODM-12)
        Schema::create('core_module_operations', function (Blueprint $table) {
            $table->id();
            $table->string('module_slug', 100)->index();
            $table->string('operation', 20);                        // install / activate / deactivate / update / uninstall
            $table->string('status', 20)->index();                  // running / success / failed / rolled_back
            $table->json('steps');
            $table->text('error')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_module_operations');
        Schema::dropIfExists('core_module_versions');
        Schema::dropIfExists('core_modules');
    }
};
