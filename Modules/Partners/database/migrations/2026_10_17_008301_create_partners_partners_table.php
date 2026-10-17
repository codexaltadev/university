<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-06 v1.3 §8: partners (attachable; logo from the Media Library; discount category = partners.discount-category). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners_partners', function (Blueprint $table) {
            $table->id();
            $table->string('partner_type', 20)->default('industry')->index();
            $table->json('name');
            $table->string('website_url', 2048)->nullable();
            $table->json('description')->nullable();
            $table->json('discount_value')->nullable();
            $table->json('discount_terms')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable()->index();
            $table->boolean('is_international')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners_partners');
    }
};
