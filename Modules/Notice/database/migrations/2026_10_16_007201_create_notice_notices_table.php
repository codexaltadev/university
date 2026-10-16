<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-05 §5 v1.4: notices (B, attachable). active_at schedules a notice; expires_at only takes it
 * off the board (it stays in the archive). Attachments are media uses with their own titles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notice_notices', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->string('reference_no', 100)->nullable()->index();
            $table->json('body')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_urgent')->default(false);
            $table->timestamp('active_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->platformColumns();

            $table->index(['is_pinned', 'active_at']);
            $table->index(['is_active', 'deleted_at', 'active_at']);   // IX-5
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notice_notices');
    }
};
