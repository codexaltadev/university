<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-05 §15 v1.11: popup, ticker, countdown and announcement bar (attachable). Which pages:
 * the site's homepage, all its pages or chosen module pages. No publish date: is_active,
 * active_at (from), expires_at (until).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('widget_type', 20)->index();
            $table->json('title')->nullable();
            $table->json('body')->nullable();
            $table->string('link_url', 2048)->nullable();
            $table->json('button_text')->nullable();
            $table->dateTime('target_at')->nullable();
            $table->string('after_end', 10)->default('hide');
            $table->json('after_end_text')->nullable();
            $table->string('frequency', 20)->default('daily');
            $table->boolean('is_dismissible')->default(true);
            $table->string('color_token', 30)->nullable();
            $table->smallInteger('priority')->default(0);
            $table->string('pages', 10)->default('home');
            $table->json('route_keys')->nullable();
            $table->boolean('include_latest_notices')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('active_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->platformColumns();

            $table->index(['widget_type', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_widgets');
    }
};
