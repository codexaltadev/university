<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-05 §9 v1.8: achievements (attachable; the achievers are assignments, role "achiever";
 * pictures are Media Library uses "image" and "gallery"). No publish date: is_active, active_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements_achievements', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('summary')->nullable();
            $table->json('body')->nullable();
            $table->date('achieved_on')->index();
            $table->json('team_name')->nullable();
            $table->json('competition')->nullable();
            $table->json('issuer')->nullable();
            $table->string('level', 20)->index();
            $table->json('position_label')->nullable();
            $table->unsignedBigInteger('related_post_id')->nullable()->index();   // News is optional: no FK
            $table->timestamp('active_at')->nullable();
            $table->platformColumns();

            $table->index(['is_active', 'achieved_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements_achievements');
    }
};
