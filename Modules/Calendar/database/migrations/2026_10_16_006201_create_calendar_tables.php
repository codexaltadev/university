<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-03 §12 v1.6: academic calendars (B) and their dates (B). Shown when is_active; no publish
 * date. Session as text until M-05; the faculties a calendar is for live in core_relations,
 * the audience (Undergraduate / Graduate) of a date in core_termables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_calendars', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->string('session_label', 50)->nullable();
            $table->unsignedSmallInteger('year');
            $table->json('duration_label')->nullable();
            $table->boolean('show_on_home')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->index('year');
            $table->index('session_label');
        });

        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_id')->constrained('calendar_calendars')->cascadeOnDelete();
            $table->json('title');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->json('date_label')->nullable();
            $table->foreignId('type_term_id')->nullable()->constrained('core_terms')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->index(['calendar_id', 'sort_order']);
            $table->index('starts_on');
            $table->index('type_term_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
        Schema::dropIfExists('calendar_calendars');
    }
};
