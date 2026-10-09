<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-01 §5.3 — P-03, pivot (P) table. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_user_scopes', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('scopeable_type', 100);   // morph alias, e.g. department.department
            $table->unsignedBigInteger('scopeable_id');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->platformPivotColumns();

            $table->primary(['user_id', 'scopeable_type', 'scopeable_id']);
            $table->index(['scopeable_type', 'scopeable_id']);   // IX-3: who works on this entity (FR-NOTI-07)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_user_scopes');
    }
};
