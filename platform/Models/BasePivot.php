<?php

namespace Platform\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base of pivot / relation / log models (P, L tables): no soft delete, no is_active (BRD Q-06).
 * Table uses $table->platformPivotColumns().
 */
abstract class BasePivot extends Model {}
