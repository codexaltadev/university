<?php

namespace Platform\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Platform\Context\SiteContext;
use Platform\Hierarchy\EntityHierarchy;
use Platform\Relations\ContentRelation;
use Platform\Relations\RelationEngine;

/**
 * Content side of the Relation Engine (FR-REL-02): News, Notice, FAQ ...
 * Register with $r->attachable(Model::class). The model should implement
 * Platform\Contracts\Scopeable — scopeKeys() comes from this trait.
 */
trait IsAttachable
{
    public static function bootIsAttachable(): void
    {
        $forget = fn (Model $model) => app(RelationEngine::class)->forgetContent($model);

        method_exists(static::class, 'forceDeleted') ? static::forceDeleted($forget) : static::deleted($forget);
    }

    public function contentRelations(): MorphMany
    {
        return $this->morphMany(ContentRelation::class, 'content');
    }

    /** "Show in" targets, e.g. ["department.department:12"]; the main site is not a target. */
    public function targetKeys(): array
    {
        return app(RelationEngine::class)->targetsOf($this);
    }

    /** Scopeable: a record belongs to the entities it is shown in (scope path "relation"). */
    public function scopeKeys(): array
    {
        return $this->targetKeys();
    }

    public function isOnMainSite(): bool
    {
        return app(RelationEngine::class)->isOnMainSite($this);
    }

    /**
     * Content of one context (metrics, Module Guide §11.2): everything for the main
     * site; for an entity what is shown in it, and with $descendants in what is below it.
     */
    public function scopeInContext(Builder $query, SiteContext $context, bool $descendants = true): Builder
    {
        if (! $context->isMicrosite()) {
            return $query;
        }

        $keys = $descendants ? app(EntityHierarchy::class)->withDescendants([$context->key()]) : [$context->key()];

        return $query->shownIn($keys);
    }

    /** Admin queries: records shown in any of these entities. */
    public function scopeShownIn(Builder $query, array $keys): Builder
    {
        $grouped = [];
        foreach ($keys as $key) {
            [$type, $id] = explode(':', $key, 2);
            $grouped[$type][] = (int) $id;
        }

        return $query->whereExists(function (QueryBuilder $sub) use ($grouped): void {
            $sub->selectRaw('1')->from('core_relations as sr')
                ->whereColumn('sr.content_id', $this->getQualifiedKeyName())
                ->where('sr.content_type', $this->getMorphClass())
                ->where(function (QueryBuilder $or) use ($grouped): void {
                    foreach ($grouped as $type => $ids) {
                        $or->orWhere(fn (QueryBuilder $q) => $q->where('sr.target_type', $type)->whereIn('sr.target_id', $ids));
                    }
                    if ($grouped === []) {
                        $or->whereRaw('1 = 0');
                    }
                });
        });
    }
}
