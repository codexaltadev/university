<?php

namespace Platform\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base of every log (L) model of a module (DB-01 §1.1): 404 log, backup runs …
 * Written by the system, never edited by hand; no soft delete, no is_active,
 * no activity log. Old rows are removed by the owning module's clean-up.
 */
abstract class LogModel extends Model {}
