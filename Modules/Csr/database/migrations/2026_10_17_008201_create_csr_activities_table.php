<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-06 v1.3 §7: CSR activities (attachable; pictures from the Media Library; category = csr.category). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('csr_activities', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('summary')->nullable();
            $table->json('body')->nullable();
            $table->string('external_url', 2048)->nullable();
            $table->string('video_url', 2048)->nullable();
            $table->date('occurred_on')->nullable()->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('active_at')->nullable();
            $table->platformColumns();

            $table->index(['is_active', 'occurred_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('csr_activities');
    }
};
