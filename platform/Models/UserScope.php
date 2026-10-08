<?php

namespace Platform\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entity a user works on (P-03, core_user_scopes).
 * Pivot-like system table: no soft delete, no is_active (BRD Q-06).
 */
class UserScope extends Model
{
    protected $table = 'core_user_scopes';

    protected $fillable = ['user_id', 'scopeable_type', 'scopeable_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function key(): string
    {
        return $this->scopeable_type.':'.$this->scopeable_id;
    }
}
