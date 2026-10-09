<?php

namespace Modules\Notification\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Platform\Models\LogModel;

/**
 * Where one entity stands for one rule (notification_reminder_states, FR-NOTI-11):
 * ok / empty / stale, when content last changed, which records are old, reminders sent.
 * Written by the daily check only.
 */
class ReminderState extends LogModel
{
    public const OK = 'ok';

    public const EMPTY = 'empty';

    public const STALE = 'stale';

    public $timestamps = false;

    protected $table = 'notification_reminder_states';

    protected $fillable = ['rule_id', 'entity_type', 'entity_id', 'status', 'last_content_at', 'stale_records', 'last_reminded_at', 'reminder_count', 'checked_at'];

    protected function casts(): array
    {
        return [
            'last_content_at' => 'datetime', 'last_reminded_at' => 'datetime', 'checked_at' => 'datetime',
            'stale_records' => 'array', 'reminder_count' => 'integer',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ReminderRule::class, 'rule_id');
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    /** Days since the content last changed; null when there is none. */
    public function days(): ?int
    {
        return $this->last_content_at ? (int) $this->last_content_at->diffInDays($this->checked_at ?? now()) : null;
    }
}
