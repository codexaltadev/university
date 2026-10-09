<?php

namespace Platform\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Platform\Assignments\Assignment;
use Platform\Assignments\AssignmentEngine;

/**
 * People can be assigned here in catalog roles (FR-REL-10): Office, Committee,
 * Department, Event ... Register with $r->assignable(Model::class, roles: [...]).
 */
trait IsAssignable
{
    public static function bootIsAssignable(): void
    {
        $forget = fn (Model $model) => app(AssignmentEngine::class)->forget($model);

        method_exists(static::class, 'forceDeleted') ? static::forceDeleted($forget) : static::deleted($forget);
    }

    public function assignments(): MorphMany
    {
        return $this->morphMany(Assignment::class, 'assignable');
    }

    /** Website list of people; see AssignmentEngine::people(). */
    public function people(string|array|null $roles = null, bool $former = false): Collection
    {
        return app(AssignmentEngine::class)->people($this, $roles, $former);
    }
}
