<?php

namespace Platform\Scope;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Platform\Contracts\Scopeable;
use Platform\Hierarchy\EntityHierarchy;
use Platform\Models\UserScope;

/**
 * Data scope engine (SAD Part B §5).
 * A user with no scope rows is unrestricted (BRL-ROLE-03); otherwise the
 * effective scope is the user's entities plus all their descendants.
 */
class ScopeResolver
{
    /** @var array<int|string, list<string>|null> */
    protected array $cache = [];

    public function __construct(protected EntityHierarchy $hierarchy) {}

    /** @return list<string>|null entity keys ("department.department:12"); null = unrestricted */
    public function keysFor(Authenticatable $user): ?array
    {
        $id = $user->getAuthIdentifier();

        if (array_key_exists($id, $this->cache)) {
            return $this->cache[$id];
        }

        $own = UserScope::query()
            ->where('user_id', $id)
            ->get()
            ->map(fn (UserScope $scope): string => $scope->key())
            ->all();

        return $this->cache[$id] = $own === [] ? null : $this->hierarchy->withDescendants($own);
    }

    /**
     * Effective scope grouped by entity type, for queries.
     *
     * @return array<string, list<int>>|null
     */
    public function idsByType(Authenticatable $user): ?array
    {
        $keys = $this->keysFor($user);

        if ($keys === null) {
            return null;
        }

        $grouped = [];
        foreach ($keys as $key) {
            [$type, $id] = explode(':', $key, 2);
            $grouped[$type][] = (int) $id;
        }

        return $grouped;
    }

    public function isScoped(Authenticatable $user): bool
    {
        return $this->keysFor($user) !== null;
    }

    public function allows(Authenticatable $user, Model $record): bool
    {
        if (! $record instanceof Scopeable) {
            return true;
        }

        $keys = $this->keysFor($user);

        if ($keys === null) {
            return true;
        }

        return array_intersect($record->scopeKeys(), $keys) !== [];
    }

    /** UserScopeChanged / EntityHierarchyChanged. */
    public function forget(): void
    {
        $this->cache = [];
    }
}
