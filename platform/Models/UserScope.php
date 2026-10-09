<?php

namespace Platform\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * One entity a user works on (P-03, core_user_scopes, DB-01 §5.3).
 * Composite key (user_id, scopeable_type, scopeable_id); remove rows with a query.
 */
class UserScope extends BasePivot
{
    protected $table = 'core_user_scopes';

    protected $primaryKey = null;

    public $incrementing = false;

    protected $fillable = ['user_id', 'scopeable_type', 'scopeable_id', 'created_by'];

    protected static function booted(): void
    {
        static::creating(fn (self $scope) => $scope->created_by ??= Auth::id());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function key(): string
    {
        return $this->scopeable_type.':'.$this->scopeable_id;
    }
}
