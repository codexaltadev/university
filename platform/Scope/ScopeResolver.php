<?php

namespace Platform\Scope;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Platform\Contracts\Scopeable;
use Platform\Models\UserScope;

/**
 * Data scope engine (SAD Part B §5).
 * A user with no scope rows is unrestricted; otherwise records must share
 * at least one entity key with the user's effective scope (own + descendants).
 */
class ScopeResolver
{
    /** @var array<string, Closure(list<int>): list<string>> entity type => children resolver */
    protected array $children = [];

    /** @var array<int|string, list<string>|null> */
    protected array $cache = [];

    /**
     * Register how to find child entities (e.g. faculty → its departments).
     * The closure receives parent ids and returns child keys ("department:3").
     */
    public function registerChildren(string $type, Closure $resolver): void
    {
        $this->children[$type] = $resolver;
        $this->cache = [];
    }

    /** @return list<string>|null null = unrestricted */
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

        return $this->cache[$id] = $own === [] ? null : $this->withDescendants($own);
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

    public function forget(): void
    {
        $this->cache = [];
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    protected function withDescendants(array $keys): array
    {
        $all = $keys;
        $queue = $keys;

        while ($queue !== []) {
            $byType = [];
            foreach ($queue as $key) {
                [$type, $id] = explode(':', $key, 2);
                $byType[$type][] = (int) $id;
            }

            $queue = [];
            foreach ($byType as $type => $ids) {
                if (! isset($this->children[$type])) {
                    continue;
                }
                foreach (($this->children[$type])($ids) as $child) {
                    if (! in_array($child, $all, true)) {
                        $all[] = $child;
                        $queue[] = $child;
                    }
                }
            }
        }

        return $all;
    }
}
