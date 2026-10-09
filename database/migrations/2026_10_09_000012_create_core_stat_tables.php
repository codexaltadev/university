<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-02 §11: C-20 Statistics (kernel engine): stat sets per owner (P-07) and their items. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_stat_sets', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type', 100)->default('site');             // main site = ("site", 0)
            $table->unsignedBigInteger('owner_id')->default(0);
            $table->string('key', 50)->default('default');                    // several sets per owner (FR-STAT-01)
            $table->json('name');
            $table->platformColumns();

            $table->unique(['owner_type', 'owner_id', 'key', 'active_key'], 'core_stat_sets_key_unique');
        });

        Schema::create('core_stat_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stat_set_id')->constrained('core_stat_sets')->cascadeOnDelete();
            $table->json('label');
            $table->string('source', 10)->default('manual');                  // manual, metric
            $table->string('metric_key', 100)->nullable()->index();
            $table->json('metric_params')->nullable();
            $table->bigInteger('manual_value')->nullable();
            $table->bigInteger('override_value')->nullable();                  // FR-STAT-06
            $table->string('rounding', 20)->nullable();                       // none, hundred, thousand, plus
            $table->string('prefix', 20)->nullable();
            $table->string('suffix', 20)->nullable();
            $table->string('icon', 100)->nullable();
            $table->date('as_of')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_stat_items');
        Schema::dropIfExists('core_stat_sets');
    }
};
