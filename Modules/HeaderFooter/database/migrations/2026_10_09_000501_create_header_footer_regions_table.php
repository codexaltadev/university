<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-02 §6: header_footer_regions — C (P-05). Configuration: no soft delete. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('header_footer_regions', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type', 100)->default('site');             // main site = ("site", 0)
            $table->unsignedBigInteger('owner_id')->default(0);
            $table->string('region', 20);                                     // topbar, header, footer
            $table->string('variant', 50)->default('default');                // the theme's variant (FR-HF-01)
            $table->json('config')->nullable();                               // toggles, footer columns and blocks (FR-HF-02/03)
            $table->json('copyright')->nullable();                            // per language, {year} placeholder (FR-HF-04)
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['owner_type', 'owner_id', 'region']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('header_footer_regions');
    }
};
