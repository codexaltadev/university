<?php

namespace Platform\Hierarchy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Platform\Registry\Registry;
use Platform\Scope\UserScopeFilter;
use Platform\Support\MorphMap;

/**
 * Ancestors and descendants of entities (SAD Part B §2, ADR-13).
 * The tree comes from relationTarget(parent: ...) registrations, never hardcoded;
 * it is small (~260 nodes at DIU), so the whole snapshot lives in one cache key.
 *
 * Node keys look like "department.department:12".
 */
class EntityHierarchy
{
    public const CACHE_KEY = 'platform:hierarchy';

    /** @var array<string, array{parent: ?string, ancestors: list<string>, descendants: list<string>}>|null */
    protected ?array $snapshot = null;

    public function __construct(protected Registry $registry) {}

    public static function key(string $type, int|string $id): string
    {
        return "{$type}:{$id}";
    }

    /** @return list<string> nearest first */
    public function ancestors(string $key): array
    {
        return $this->snapshot()[$key]['ancestors'] ?? [];
    }

    /** @return list<string> */
    public function descendants(string $key): array
    {
        return $this->snapshot()[$key]['descendants'] ?? [];
    }

    public function parent(string $key): ?string
    {
        return $this->snapshot()[$key]['parent'] ?? null;
    }

    /**
     * Keys plus all their descendants.
     *
     * @param  list<string>  $keys
     * @return list<string>
     */
    public function withDescendants(array $keys): array
    {
        $all = $keys;

        foreach ($keys as $key) {
            array_push($all, ...$this->descendants($key));
        }

        return array_values(array_unique($all));
    }

    /** Forget the snapshot (entity created / moved / deleted, module (de)activated). */
    public function flush(): void
    {
        $this->snapshot = null;
        Cache::forget(self::CACHE_KEY);
    }

    /** Keep the snapshot fresh when target records change. */
    public function watch(): void
    {
        foreach ($this->registry->all('relationTarget') as $entry) {
            $model = $entry['model'];

            foreach (['saved', 'deleted', 'restored'] as $event) {
                if (method_exists($model, $event)) {
                    $model::$event(fn () => $this->flush());
                }
            }
        }
    }

    /** @return array<string, array{parent: ?string, ancestors: list<string>, descendants: list<string>}> */
    protected function snapshot(): array
    {
        return $this->snapshot ??= Cache::rememberForever(self::CACHE_KEY, fn (): array => $this->build());
    }

    /** @return array<string, array{parent: ?string, ancestors: list<string>, descendants: list<string>}> */
    protected function build(): array
    {
        $parents = [];

        foreach ($this->registry->all('relationTarget') as $entry) {
            /** @var class-string<Model> $model */
            $model = $entry['model'];
            $type = MorphMap::alias($model);
            $parentType = $entry['parent'] ? MorphMap::alias($entry['parent']) : null;
            $parentKey = $entry['parentKey'];

            $columns = array_filter([(new $model)->getKeyName(), $parentKey]);

            foreach ($model::query()->withoutGlobalScope(UserScopeFilter::class)->get($columns) as $row) {
                $parents[self::key($type, $row->getKey())] = $parentType && $row->{$parentKey}
                    ? self::key($parentType, $row->{$parentKey})
                    : null;
            }
        }

        $nodes = [];

        foreach ($parents as $key => $parent) {
            $ancestors = [];
            for ($p = $parent; $p !== null && ! in_array($p, $ancestors, true); $p = $parents[$p] ?? null) {
                $ancestors[] = $p;
            }

            $nodes[$key] = ['parent' => $parent, 'ancestors' => $ancestors, 'descendants' => []];
        }

        foreach ($nodes as $key => $node) {
            foreach ($node['ancestors'] as $ancestor) {
                if (isset($nodes[$ancestor])) {
                    $nodes[$ancestor]['descendants'][] = $key;
                }
            }
        }

        return $nodes;
    }
}
