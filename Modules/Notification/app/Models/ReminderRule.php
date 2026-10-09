<?php

namespace Modules\Notification\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * "Department Notices must be updated every 90 days; remind every 7" (FR-NOTI-09,
 * notification_reminder_rules). One rule per module and entity type.
 */
class ReminderRule extends BaseModel
{
    use HasTranslations;

    public const MODES = ['latest', 'each_record'];

    public static string $titleAttribute = 'module_slug';

    protected $table = 'notification_reminder_rules';

    protected $fillable = [
        'module_slug', 'entity_type', 'max_days_without_update', 'remind_every_days', 'is_mandatory', 'check_mode',
        'empty_message', 'stale_message', 'admin_empty_message', 'admin_stale_message', 'is_active',
    ];

    public array $translatable = ['empty_message', 'stale_message', 'admin_empty_message', 'admin_stale_message'];

    protected function casts(): array
    {
        return ['is_mandatory' => 'boolean', 'max_days_without_update' => 'integer', 'remind_every_days' => 'integer'];
    }

    public function states(): HasMany
    {
        return $this->hasMany(ReminderState::class, 'rule_id');
    }
}
