<?php

namespace Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Platform\Concerns\LogsPlatformActivity;

/**
 * Base of every configuration (C) model of a module (DB-01 §1.1): header / footer
 * regions, homepage layouts … No soft delete and no created_by — one row per owner
 * and place, changed in place; is_active where the table has it; updated_by and the
 * activity log record who changed what.
 */
abstract class ConfigModel extends Model
{
    use LogsPlatformActivity;

    /** Configuration tables carry updated_by (DB-02 §6, §7). */
    protected static function booted(): void
    {
        static::saving(fn (Model $model) => $model->updated_by = Auth::id() ?? $model->updated_by);
    }
}
