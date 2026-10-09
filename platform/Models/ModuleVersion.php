<?php

namespace Platform\Models;

use Illuminate\Database\Eloquent\Model;

/** Version history of a module (core_module_versions, DB-01 §3.2). Log table. */
class ModuleVersion extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'core_module_versions';

    protected $guarded = [];
}
