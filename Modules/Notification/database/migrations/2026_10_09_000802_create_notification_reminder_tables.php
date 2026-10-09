<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-02 §14.4: Content Update Reminder — rules (B) and states per rule and entity (S). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_reminder_rules', function (Blueprint $table) {
            $table->id();
            $table->string('module_slug', 100)->index();                       // freshness source (FR-NOTI-10)
            $table->string('entity_type', 100);                                 // e.g. department.department
            $table->unsignedSmallInteger('max_days_without_update')->default(90);
            $table->unsignedSmallInteger('remind_every_days')->default(7);
            $table->boolean('is_mandatory')->default(true);
            $table->string('check_mode', 20)->default('latest');                // latest, each_record
            $table->json('empty_message')->nullable();
            $table->json('stale_message')->nullable();
            $table->json('admin_empty_message')->nullable();
            $table->json('admin_stale_message')->nullable();
            $table->platformColumns();

            $table->unique(['module_slug', 'entity_type', 'active_key'], 'notification_reminder_rules_unique');
        });

        Schema::create('notification_reminder_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('notification_reminder_rules')->cascadeOnDelete();
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');
            $table->string('status', 10)->default('ok')->index();               // ok, empty, stale
            $table->timestamp('last_content_at')->nullable();
            $table->json('stale_records')->nullable();
            $table->timestamp('last_reminded_at')->nullable();
            $table->unsignedInteger('reminder_count')->default(0);
            $table->timestamp('checked_at')->nullable();

            $table->unique(['rule_id', 'entity_type', 'entity_id'], 'notification_reminder_states_unique');
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_reminder_states');
        Schema::dropIfExists('notification_reminder_rules');
    }
};
