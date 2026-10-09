<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-01 §13: legacy row → new record, so a later step can turn an old department_id
 * into the new one and a second run updates instead of duplicating (M-3).
 * Temporary: archived after go-live.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('migration_id_map', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_table', 100);
            $table->unsignedBigInteger('legacy_id');
            $table->string('new_type', 100);
            $table->unsignedBigInteger('new_id');
            $table->timestamps();

            $table->unique(['legacy_id', 'legacy_table']);   // *_id first (IX-2)
            $table->index(['new_id', 'new_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migration_id_map');
    }
};
