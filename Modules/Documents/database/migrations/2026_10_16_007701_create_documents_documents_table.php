<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-05 §12 v1.9: documents (attachable) — the file is a Media Library use ("file", "thumbnail");
 * type from a fixed list (its own fields), category from documents.category; a newer version
 * supersedes an older one; flipbook, read-only and private switches; download count.
 * No publish date: is_active, active_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 20)->default('other');
            $table->json('title');
            $table->json('description')->nullable();
            $table->string('external_url', 2048)->nullable();
            $table->string('version_label', 30)->nullable();
            $table->date('approved_on')->nullable();
            $table->date('effective_on')->nullable();
            $table->string('session_label', 50)->nullable();
            $table->unsignedBigInteger('program_id')->nullable()->index();   // Program is optional: no FK
            $table->foreignId('superseded_by_id')->nullable()->constrained('documents_documents')->nullOnDelete();
            $table->boolean('show_as_flipbook')->default(false);
            $table->boolean('allow_download')->default(true);
            $table->boolean('is_private')->default(false);
            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('active_at')->nullable();
            $table->platformColumns();

            $table->index(['document_type', 'is_active', 'active_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents_documents');
    }
};
