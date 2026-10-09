<?php

namespace Platform\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Platform\Assignments\Assignment;
use Platform\Assignments\AssignmentEngine;

/**
 * A person directory (FR-REL-09). The model implements Platform\Contracts\PersonSource
 * and is registered with $r->personSource(Model::class).
 */
trait IsPersonSource
{
    public static function bootIsPersonSource(): void
    {
        $forget = fn (Model $model) => app(AssignmentEngine::class)->forget($model);

        method_exists(static::class, 'forceDeleted') ? static::forceDeleted($forget) : static::deleted($forget);
    }

    public function personAssignments(): MorphMany
    {
        return $this->morphMany(Assignment::class, 'person');
    }
}
