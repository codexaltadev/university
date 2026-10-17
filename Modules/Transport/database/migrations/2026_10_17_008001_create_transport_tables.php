<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-06 v1.2 §2 (owner 2026-10-17: the old transport system goes, the data lives here): service
 * types, stops, routes; a route's variant per service type with its ordered stops, days off and
 * departures. Which service is running now is a setting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_service_types', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('code', 50);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->unique(['code', 'active_key']);
        });

        Schema::create('transport_stops', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('landmark')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();
        });

        Schema::create('transport_routes', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('code', 30)->nullable();
            $table->string('route_group', 10)->default('regular');
            $table->unsignedBigInteger('campus_id')->nullable()->index();   // Campus is optional: no FK
            $table->string('map_url', 2048)->nullable();
            $table->json('message')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->unique(['code', 'active_key']);
            $table->index(['route_group', 'sort_order']);
        });

        Schema::create('transport_route_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('transport_routes')->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained('transport_service_types')->restrictOnDelete();
            $table->json('days_off')->nullable();
            $table->json('message')->nullable();
            $table->platformColumns();

            $table->unique(['route_id', 'service_type_id', 'active_key'], 'transport_variants_route_service_unique');
            $table->index(['service_type_id', 'route_id']);
        });

        Schema::create('transport_variant_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('transport_route_variants')->cascadeOnDelete();
            $table->foreignId('stop_id')->constrained('transport_stops')->restrictOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->platformPivotColumns();

            $table->unique(['variant_id', 'stop_id']);
        });

        Schema::create('transport_departures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('transport_route_variants')->cascadeOnDelete();
            $table->string('direction', 12);
            $table->time('departs_at');
            $table->string('trip_type', 10)->default('regular');
            $table->json('days_off')->nullable();
            $table->json('stop_times')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->index(['variant_id', 'direction', 'departs_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_departures');
        Schema::dropIfExists('transport_variant_stops');
        Schema::dropIfExists('transport_route_variants');
        Schema::dropIfExists('transport_routes');
        Schema::dropIfExists('transport_stops');
        Schema::dropIfExists('transport_service_types');
    }
};
