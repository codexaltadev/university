<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-02 §12.3: view counter (P-17, FR-CNT-C08). One row per record; page views are
 * gathered in the cache and written in batches (uwms:views:flush), not one write per view.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_view_counts', function (Blueprint $table) {
            $table->string('viewable_type', 100);
            $table->unsignedBigInteger('viewable_id');
            $table->unsignedBigInteger('total')->default(0);
            $table->timestamp('last_viewed_at')->nullable();

            $table->primary(['viewable_type', 'viewable_id']);
            $table->index(['viewable_type', 'total']);   // "Most viewed"
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_view_counts');
    }
};
