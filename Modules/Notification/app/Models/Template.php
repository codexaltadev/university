<?php

namespace Modules\Notification\Models;

use Platform\Models\ConfigModel;
use Spatie\Translatable\HasTranslations;

/**
 * The admin's wording of one notification type (notification_templates,
 * FR-NOTI-06): subject and body per language with {placeholders}. Inactive or
 * missing = the type's own text.
 */
class Template extends ConfigModel
{
    use HasTranslations;

    public static string $titleAttribute = 'key';

    protected $table = 'notification_templates';

    protected $fillable = ['key', 'subject', 'body', 'is_active'];

    public array $translatable = ['subject', 'body'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
