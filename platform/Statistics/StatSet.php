<?php

namespace Platform\Statistics;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasOwner;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * A group of counters of one owner (core_stat_sets, P-07): "Homepage counters" of
 * the main site, of Engineering, of CSE … An owner may have several (key).
 */
class StatSet extends BaseModel implements Scopeable
{
    use AppliesUserScope;
    use HasOwner;
    use HasTranslations;

    protected $table = 'core_stat_sets';

    protected $fillable = ['key', 'name', 'is_active'];

    public array $translatable = ['name'];

    protected $attributes = ['key' => 'default'];

    public function items(): HasMany
    {
        return $this->hasMany(StatItem::class)->orderBy('sort_order')->orderBy('id');
    }
}
