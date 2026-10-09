<?php

namespace Modules\Homepage\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasOwner;
use Platform\Contracts\Scopeable;
use Platform\Models\ConfigModel;

/**
 * The homepage of the main site or of one microsite (homepage_layouts, P-06). A
 * microsite without one (or with an inactive one) shows the theme's default
 * microsite homepage, not its parent's (FR-HOME-08).
 */
class Layout extends ConfigModel implements Scopeable
{
    use AppliesUserScope;
    use HasOwner;

    protected $table = 'homepage_layouts';

    protected $fillable = ['is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(HomeSection::class, 'layout_id')->orderBy('sort_order')->orderBy('id');
    }
}
