<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasSlug;
use Platform\Concerns\IsRelationTarget;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;

/** Test entity that behaves like a Faculty ("test.parent", microsite /test-faculty/{slug}). */
class FixtureParent extends BaseModel implements Scopeable
{
    use AppliesUserScope;
    use HasSlug;
    use IsRelationTarget;

    protected $table = 'fixture_parents';

    protected $guarded = [];

    protected array $guardedChildren = ['children'];

    public function children(): HasMany
    {
        return $this->hasMany(FixtureChild::class, 'parent_id');
    }
}
