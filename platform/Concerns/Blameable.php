<?php

namespace Platform\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * created_by / updated_by / deleted_by (FR-GEN-05).
 */
trait Blameable
{
    public static function bootBlameable(): void
    {
        static::creating(function (Model $model): void {
            $id = Auth::id();
            $model->created_by ??= $id;
            $model->updated_by ??= $id;
        });

        static::updating(function (Model $model): void {
            if (! $model->isDirty('updated_by')) {
                $model->updated_by = Auth::id();
            }
        });

        if (method_exists(static::class, 'softDeleted')) {
            static::softDeleted(function (Model $model): void {
                $model->deleted_by = Auth::id();
                $model->saveQuietly();
            });

            static::restoring(function (Model $model): void {
                $model->deleted_by = null;
            });
        }
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
