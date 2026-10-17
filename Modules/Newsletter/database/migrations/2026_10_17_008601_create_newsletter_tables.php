<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-06 v1.3 §10: lists, subscribers (double opt-in, suppression), campaigns and their deliveries. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_lists', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50);
            $table->json('name');
            $table->json('description')->nullable();
            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->unique(['key', 'active_key']);
        });

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email', 191);
            $table->string('name', 191)->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->char('token', 64)->unique();
            $table->timestamp('verified_at')->nullable();
            $table->string('source', 30)->default('footer');
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('unsubscribe_reason', 255)->nullable();
            $table->platformColumns();

            $table->unique(['email', 'active_key']);
        });

        Schema::create('newsletter_list_subscriber', function (Blueprint $table) {
            $table->foreignId('list_id')->constrained('newsletter_lists')->cascadeOnDelete();
            $table->foreignId('subscriber_id')->constrained('newsletter_subscribers')->cascadeOnDelete();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();

            $table->primary(['list_id', 'subscriber_id']);
            $table->index('subscriber_id');
        });

        Schema::create('newsletter_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('subject', 255);
            $table->string('locale', 10)->default('en');
            $table->longText('body');
            $table->string('source_url', 2048)->nullable();
            $table->string('source_label', 255)->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('send_status', 20)->default('draft')->index();
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('bounce_count')->default(0);
            $table->platformColumns();
        });

        Schema::create('newsletter_campaign_lists', function (Blueprint $table) {
            $table->foreignId('campaign_id')->constrained('newsletter_campaigns')->cascadeOnDelete();
            $table->foreignId('list_id')->constrained('newsletter_lists')->restrictOnDelete();

            $table->primary(['campaign_id', 'list_id']);
            $table->index('list_id');
        });

        Schema::create('newsletter_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('newsletter_campaigns')->cascadeOnDelete();
            $table->foreignId('subscriber_id')->constrained('newsletter_subscribers')->cascadeOnDelete();
            $table->string('status', 20)->default('queued')->index();
            $table->string('error', 500)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'subscriber_id']);
            $table->index('subscriber_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_deliveries');
        Schema::dropIfExists('newsletter_campaign_lists');
        Schema::dropIfExists('newsletter_campaigns');
        Schema::dropIfExists('newsletter_list_subscriber');
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('newsletter_lists');
    }
};
