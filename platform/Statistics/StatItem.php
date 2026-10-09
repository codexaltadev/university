<?php

namespace Platform\Statistics;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * One counter (core_stat_items): a manual number or a module's metric, optional
 * override and rounding, prefix / suffix, icon, "as of" date (FR-STAT-02/06).
 */
class StatItem extends BaseModel
{
    use HasTranslations;

    public const SOURCES = ['manual', 'metric'];

    public const ROUNDINGS = ['none', 'hundred', 'thousand', 'plus'];

    public static string $titleAttribute = 'label';

    protected $table = 'core_stat_items';

    protected $fillable = [
        'stat_set_id', 'label', 'source', 'metric_key', 'metric_params', 'manual_value', 'override_value',
        'rounding', 'prefix', 'suffix', 'icon', 'as_of', 'sort_order', 'is_active',
    ];

    public array $translatable = ['label'];

    protected function casts(): array
    {
        return ['metric_params' => 'array', 'manual_value' => 'integer', 'override_value' => 'integer', 'as_of' => 'date', 'sort_order' => 'integer'];
    }

    public function set(): BelongsTo
    {
        return $this->belongsTo(StatSet::class, 'stat_set_id');
    }
}
