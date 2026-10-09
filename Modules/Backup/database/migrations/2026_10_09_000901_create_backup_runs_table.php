<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-02 §15: backup_runs — L. One row per backup attempt. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_runs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index();                               // database, full, pre_install
            $table->string('status', 20)->index();                             // running, success, failed
            $table->string('disk', 50);
            $table->string('path', 500)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->text('error')->nullable();
            $table->string('reason', 255)->nullable();                         // "install news 1.2.0" …
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();   // null = scheduler
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_runs');
    }
};
