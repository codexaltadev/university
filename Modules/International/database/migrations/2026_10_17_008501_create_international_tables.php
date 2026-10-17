<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-06 v1.3 §6: countries (master data, flag from the Media Library), partner institutions,
 * conferences and visitors (all three attachable).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('international_countries', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->char('iso2', 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->unique(['iso2', 'active_key']);
        });

        Schema::create('international_partners', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->foreignId('country_id')->constrained('international_countries')->restrictOnDelete();
            $table->string('website_url', 2048)->nullable();
            $table->string('agreement_type', 20)->default('mou')->index();
            $table->date('signed_on')->nullable();
            $table->date('expires_on')->nullable()->index();
            $table->json('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->index(['country_id', 'agreement_type']);
        });

        Schema::create('international_conferences', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('body')->nullable();
            $table->date('starts_on')->nullable()->index();
            $table->date('ends_on')->nullable();
            $table->json('venue')->nullable();
            $table->string('website_url', 2048)->nullable();
            $table->json('organizer_label')->nullable();
            $table->platformColumns();
        });

        Schema::create('international_visitors', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('designation')->nullable();
            $table->json('organization')->nullable();
            $table->foreignId('country_id')->nullable()->constrained('international_countries')->nullOnDelete();
            $table->date('visited_on')->index();
            $table->json('body')->nullable();
            $table->boolean('is_external')->default(true);
            $table->unsignedBigInteger('album_id')->nullable()->index();   // Gallery is optional: no foreign key
            $table->platformColumns();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('international_visitors');
        Schema::dropIfExists('international_conferences');
        Schema::dropIfExists('international_partners');
        Schema::dropIfExists('international_countries');
    }
};
