<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-05 §14 v1.10: important links (attachable — each place its own; icon from the Media Library,
 * group from links.group); the weekly check keeps the last answer (FR-LINK-03).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('links_links', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->string('url', 2048);
            $table->json('description')->nullable();
            $table->boolean('open_in_new_tab')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->unsignedSmallInteger('last_status_code')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('links_links');
    }
};
