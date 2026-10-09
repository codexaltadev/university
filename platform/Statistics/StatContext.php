<?php

namespace Platform\Statistics;

use Platform\Context\MicrositeOwners;
use Platform\Context\SiteContext;
use Platform\Hierarchy\EntityHierarchy;
use Platform\Relations\RelationEngine;

/**
 * Where a metric is counted (SAD Part B §7.2): the main site (everything) or an
 * entity with everything below it (Engineering = its departments and programs).
 */
final class StatContext
{
    private function __construct(public readonly string $ownerKey) {}

    public static function of(string $ownerKey): self
    {
        return new self($ownerKey);
    }

    public static function site(): self
    {
        return new self(RelationEngine::SITE_KEY);
    }

    public function isSite(): bool
    {
        return $this->ownerKey === RelationEngine::SITE_KEY;
    }

    /** @return list<string> the entity and its descendants; [] for the main site (no limit) */
    public function keys(): array
    {
        return $this->isSite() ? [] : app(EntityHierarchy::class)->withDescendants([$this->ownerKey]);
    }

    /** @return list<int> ids of one entity type inside this context (main site: null = all) */
    public function idsOf(string $alias): ?array
    {
        if ($this->isSite()) {
            return null;
        }

        return collect($this->keys())
            ->filter(fn (string $key): bool => str_starts_with($key, $alias.':'))
            ->map(fn (string $key): int => (int) substr($key, strlen($alias) + 1))
            ->values()->all();
    }

    public function asSiteContext(): SiteContext
    {
        return MicrositeOwners::context($this->ownerKey);
    }
}
