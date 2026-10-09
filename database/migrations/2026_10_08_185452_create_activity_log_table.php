<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-01 §6.1 — spatie activitylog v5 table plus platform columns. Log (L) table. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name', 50)->nullable()->index();      // model / auth / system
            $table->text('description');
            $table->string('subject_type', 100)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('event', 30)->nullable()->index();
            $table->string('causer_type', 100)->nullable();
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->json('attribute_changes')->nullable();            // {old, attributes}: changed fields only
            $table->json('properties')->nullable();
            $table->string('module_slug', 100)->nullable()->index();   // FR-LOG-04 filter
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['causer_type', 'causer_id']);
            $table->index('created_at');                               // IX-11: retention cleanup
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
