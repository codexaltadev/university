<?php

namespace Tests\Fixtures\Models;

use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasExpiry;
use Platform\Concerns\HasPublishDate;
use Platform\Concerns\IsAssignable;
use Platform\Concerns\IsRelationTarget;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;

/** Test entity that behaves like a Department ("test.child", parent: test.parent). */
class FixtureChild extends BaseModel implements Scopeable
{
    use AppliesUserScope;
    use HasExpiry;
    use HasPublishDate;
    use IsAssignable;
    use IsRelationTarget;

    protected $table = 'fixture_children';

    protected $guarded = [];
}
