<?php

namespace Platform\Support;

use Illuminate\Database\Schema\Blueprint;

/**
 * Migration macros (SAD Part A §6.5, DB-01 §1.1).
 */
class BlueprintMacros
{
    public static function register(): void
    {
        /*
         * Standard columns of every business (B) table:
         * is_active, created_by, updated_by, deleted_by, timestamps, deleted_at,
         * active_key (1 while not deleted, NULL after soft delete) and IX-5.
         *
         * Unique business keys include active_key instead of deleted_at, because
         * MySQL treats NULLs as distinct (IX-8):  $table->unique(['code', 'active_key']);
         */
        Blueprint::macro('platformColumns', function (): void {
            /** @var Blueprint $this */
            $this->boolean('is_active')->default(true);
            $this->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $this->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $this->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $this->timestamps();
            $this->softDeletes();
            $this->unsignedTinyInteger('active_key')->nullable()->storedAs('IF(`deleted_at` IS NULL, 1, NULL)');

            $this->index(['is_active', 'deleted_at']);
        });

        /** Pivot / relation (P) tables: no soft delete, no is_active (BRD Q-06). */
        Blueprint::macro('platformPivotColumns', function (): void {
            /** @var Blueprint $this */
            $this->timestamps();
        });
    }
}
