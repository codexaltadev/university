<?php

namespace Platform\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An entity moved to another parent, e.g. a Department to another Faculty (FR-DEPT-08):
 * scope, menu / header / footer fallback and the hierarchy are worked out again.
 */
class EntityHierarchyChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Model $entity,
        public readonly ?string $fromParent,
        public readonly ?string $toParent,
    ) {}
}
