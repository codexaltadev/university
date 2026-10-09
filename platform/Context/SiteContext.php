<?php

namespace Platform\Context;

use Illuminate\Database\Eloquent\Model;
use Platform\Hierarchy\EntityHierarchy;
use Platform\Relations\RelationEngine;

/**
 * Where a public request is: the main site or one microsite entity
 * (SAD Part B §6.1). Chain = the entity, its ancestors, then the main site.
 */
final class SiteContext
{
    private function __construct(public readonly ?Model $entity) {}

    public static function site(): self
    {
        return new self(null);
    }

    public static function for(?Model $entity): self
    {
        return new self($entity);
    }

    public function isMicrosite(): bool
    {
        return $this->entity !== null;
    }

    /** "department.department:12", or "site:0" for the main site. */
    public function key(): string
    {
        return $this->entity
            ? EntityHierarchy::key($this->entity->getMorphClass(), $this->entity->getKey())
            : RelationEngine::SITE_KEY;
    }

    /** @return list<string> entity, ancestors (nearest first), main site */
    public function chain(): array
    {
        if (! $this->entity) {
            return [RelationEngine::SITE_KEY];
        }

        return [$this->key(), ...app(EntityHierarchy::class)->ancestors($this->key()), RelationEngine::SITE_KEY];
    }

    public function settings(): ?MicrositeSetting
    {
        return $this->entity
            ? MicrositeSetting::query()->where('owner_type', $this->entity->getMorphClass())->where('owner_id', $this->entity->getKey())->first()
            : null;
    }
}
