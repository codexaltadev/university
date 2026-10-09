<?php

namespace Platform\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Platform\Context\MicrositeResolver;
use Platform\Relations\RelationEngine;
use Platform\Scope\UserScopeFilter;

/**
 * A record that belongs to the main site or to one entity (owner_type / owner_id,
 * DB-02 §1.2: main site = ("site", 0), never NULL): pages, menus, header / footer,
 * homepage, statistics (P-04 … P-08). With AppliesUserScope a scoped user sees only
 * the records owned by entities of their scope (path "owner").
 */
trait HasOwner
{
    public function initializeHasOwner(): void
    {
        $this->mergeFillable(['owner_type', 'owner_id', 'owner_key']);
        $this->mergeCasts(['owner_id' => 'integer']);
        $this->attributes['owner_type'] ??= RelationEngine::SITE_TYPE;
        $this->attributes['owner_id'] ??= 0;
    }

    /** "site:0" or "department.department:12". */
    public function ownerKey(): string
    {
        return "{$this->owner_type}:{$this->owner_id}";
    }

    public function setOwnerKey(string $key): static
    {
        [$this->owner_type, $id] = explode(':', $key, 2);
        $this->owner_id = (int) $id;

        return $this;
    }

    /** Forms set the owner as one value: ['owner_key' => 'department.department:12']. */
    public function setOwnerKeyAttribute(?string $key): void
    {
        $this->setOwnerKey($key ?: RelationEngine::SITE_KEY);
    }

    public function getOwnerKeyAttribute(): string
    {
        return $this->ownerKey();
    }

    public function isMainSiteOwned(): bool
    {
        return $this->owner_type === RelationEngine::SITE_TYPE;
    }

    /** The owning entity, or null for the main site. */
    public function ownerEntity(): ?Model
    {
        if ($this->isMainSiteOwned()) {
            return null;
        }

        $model = Relation::getMorphedModel($this->owner_type);

        return $model ? $model::query()->withoutGlobalScope(UserScopeFilter::class)->find($this->owner_id) : null;
    }

    /** Public address prefix: "" for the main site, "/department/cse" for an entity. */
    public function ownerUrlPrefix(?string $locale = null): string
    {
        $entity = $this->ownerEntity();

        return $entity ? (string) app(MicrositeResolver::class)->urlFor($entity, $locale) : '';
    }

    public function scopeOwnedBy(Builder $query, string $key): Builder
    {
        [$type, $id] = explode(':', $key, 2);

        return $query->where($this->qualifyColumn('owner_type'), $type)->where($this->qualifyColumn('owner_id'), (int) $id);
    }

    /** Scopeable: a record belongs to its owner. */
    public function scopeKeys(): array
    {
        return [$this->ownerKey()];
    }
}
