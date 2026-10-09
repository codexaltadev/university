<?php

namespace Modules\Notification\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Models\ConfigModel;

/**
 * A user's own channels for one notification type (notification_preferences,
 * FR-NOTI-05). No row = the type's default channels.
 */
class Preference extends ConfigModel
{
    protected $table = 'notification_preferences';

    protected $fillable = ['user_id', 'type', 'channels'];

    protected function casts(): array
    {
        return ['channels' => 'array'];
    }

    /** The table has no updated_by: the owner is the user (DB-02 §14.2). */
    protected static function booted(): void {}

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
