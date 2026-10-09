<?php

namespace Platform\Concerns;

use Illuminate\Database\Eloquent\Model;
use Platform\Hierarchy\EntityHierarchy;
use Platform\Relations\RelationEngine;

/**
 * Target side of the Relation Engine and a node of the entity hierarchy
 * (FR-REL-01): Faculty, Department, Program, Office, Lab. Register with
 * $r->relationTarget(Model::class, parent: ..., parentKey: ...).
 * The model should implement Platform\Contracts\Scopeable (scope path "self").
 */
trait IsRelationTarget
{
    public static function bootIsRelationTarget(): void
    {
        $forget = fn (Model $model) => app(RelationEngine::class)->forgetTarget($model);

        method_exists(static::class, 'forceDeleted') ? static::forceDeleted($forget) : static::deleted($forget);
    }

    /** "department.department:12" */
    public function entityKey(): string
    {
        return EntityHierarchy::key($this->getMorphClass(), $this->getKey());
    }

    /** Scopeable: a user scoped to this entity or an ancestor may manage it. */
    public function scopeKeys(): array
    {
        return [$this->entityKey()];
    }

    /** @return list<string> */
    public function ancestorKeys(): array
    {
        return app(EntityHierarchy::class)->ancestors($this->entityKey());
    }

    /** @return list<string> */
    public function descendantKeys(): array
    {
        return app(EntityHierarchy::class)->descendants($this->entityKey());
    }
}
