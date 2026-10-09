<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-02 §14.2 notification_preferences — C and §14.3 notification_templates — C. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 150);                                       // registry key, e.g. inquiry.received
            $table->json('channels');                                          // ["database", "mail"]
            $table->timestamps();

            $table->unique(['user_id', 'type']);
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 150)->unique();
            $table->json('subject');
            $table->json('body');
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('notification_preferences');
    }
};
