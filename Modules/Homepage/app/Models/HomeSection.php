<?php

namespace Modules\Homepage\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Concerns\HasMediaUsage;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/** One section of a homepage (homepage_sections): its type, heading, settings, order and schedule. */
class HomeSection extends BaseModel
{
    use HasMediaUsage;      // pictures in its settings (hero slides, image + text)
    use HasTranslations;

    public static string $titleAttribute = 'section_type';

    protected $table = 'homepage_sections';

    protected $fillable = ['layout_id', 'section_type', 'heading', 'subheading', 'settings', 'sort_order', 'visible_from', 'visible_until', 'is_active'];

    public array $translatable = ['heading', 'subheading'];

    protected function casts(): array
    {
        return ['settings' => 'array', 'sort_order' => 'integer', 'visible_from' => 'datetime', 'visible_until' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saved(fn (HomeSection $section) => $section->syncMedia('settings', static::imageIds((array) $section->settings)));
    }

    /** @return list<int> every "image" id anywhere in the settings */
    public static function imageIds(array $settings): array
    {
        $ids = [];
        array_walk_recursive($settings, function ($value, $key) use (&$ids): void {
            if ($key === 'image' && is_numeric($value)) {
                $ids[] = (int) $value;
            }
        });

        return array_values(array_unique($ids));
    }

    public function layout(): BelongsTo
    {
        return $this->belongsTo(Layout::class, 'layout_id');
    }

    /** FR-HOME-06 */
    public function isScheduledNow(): bool
    {
        return ($this->visible_from === null || $this->visible_from->isPast())
            && ($this->visible_until === null || $this->visible_until->isFuture());
    }
}
