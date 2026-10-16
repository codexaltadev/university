<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-05 §4 v1.3: events (B, attachable, assignable for speakers). Upcoming / ongoing / past come
 * from the dates; active_at is when it starts to show on the website (not the event's date).
 * Venue is words until the Campus module; the photos are a Media gallery until Gallery.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_events', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('summary')->nullable();
            $table->json('body')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable()->index();
            $table->boolean('is_all_day')->default(false);
            $table->json('venue')->nullable();
            $table->string('online_url', 2048)->nullable();
            $table->json('organizer_label')->nullable();
            $table->string('registration_url', 2048)->nullable();
            $table->dateTime('registration_ends_at')->nullable();
            $table->timestamp('active_at')->nullable()->index();
            $table->platformColumns();

            $table->index(['starts_at', 'is_active']);
            $table->index(['is_active', 'deleted_at', 'active_at']);   // IX-5
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_events');
    }
};
