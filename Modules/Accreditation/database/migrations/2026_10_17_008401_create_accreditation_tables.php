<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-06 v1.3 §5: accreditations and rankings (both attachable; logo, certificate, badge from the Media Library). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accreditation_accreditations', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('agency')->nullable();
            $table->json('body')->nullable();
            $table->string('website_url', 2048)->nullable();
            $table->date('awarded_on')->nullable();
            $table->date('expires_on')->nullable()->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();
        });

        Schema::create('accreditation_rankings', function (Blueprint $table) {
            $table->id();
            $table->string('agency_key', 50);
            $table->json('agency_name');
            $table->unsignedSmallInteger('year');
            $table->string('scope', 20)->default('overall');
            $table->json('subject')->nullable();
            $table->string('subject_key', 100)->default('');   // '' for overall — a NULL would never clash (IX-8)
            $table->string('rank_label', 30);
            $table->unsignedInteger('rank_value')->nullable();
            $table->json('summary')->nullable();
            $table->json('body')->nullable();
            $table->string('ranking_url', 2048)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->index(['agency_key', 'year']);
            $table->unique(['agency_key', 'year', 'scope', 'subject_key', 'active_key'], 'accreditation_rankings_entry_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accreditation_rankings');
        Schema::dropIfExists('accreditation_accreditations');
    }
};
