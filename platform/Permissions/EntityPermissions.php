<?php

namespace Platform\Permissions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Platform\Contracts\HasEntityPermissions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Dynamic per-entity permissions (FR-ROLE-07): "{Action}:{Entity}#{id}".
 * Created when an Office / Lab is created, removed when it is force-deleted.
 */
class EntityPermissions
{
    /** @var list<class-string<Model&HasEntityPermissions>> */
    protected array $models = [];

    /** @param class-string<Model&HasEntityPermissions> $model */
    public function register(string $model): void
    {
        if (in_array($model, $this->models, true)) {
            return;
        }

        $this->models[] = $model;

        $model::created(fn (Model $record) => $this->sync($record));

        // Soft-deletable entities keep their permission until permanently deleted.
        method_exists($model, 'forceDeleted')
            ? $model::forceDeleted(fn (Model $record) => $this->remove($record))
            : $model::deleted(fn (Model $record) => $this->remove($record));
    }

    public static function key(string $action, string $subject, int|string $id): string
    {
        return "{$action}:{$subject}#{$id}";
    }

    /** @param Model&HasEntityPermissions $record */
    public function sync(Model $record): void
    {
        foreach ($this->keysFor($record) as $key) {
            Permission::findOrCreate($key, $this->guard());
        }
    }

    /** @param Model&HasEntityPermissions $record */
    public function remove(Model $record): void
    {
        Permission::query()
            ->whereIn('name', $this->keysFor($record))
            ->where('guard_name', $this->guard())
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @param Model&HasEntityPermissions $record */
    public function userCanAny(Authenticatable $user, Model $record): bool
    {
        foreach ($this->keysFor($record) as $key) {
            if ($user->hasPermissionTo($key, $this->guard())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Role UI options grouped by entity type:
     * ["Office" => ["Manage:Office#1" => "Manage: Registrar Office", ...]].
     *
     * @return array<string, array<string, string>>
     */
    public function options(): array
    {
        $groups = [];

        foreach ($this->models as $model) {
            $subject = $model::entityPermissionSubject();

            foreach ($model::query()->get() as $record) {
                foreach ($model::entityPermissionActions() as $action) {
                    $groups[$subject][self::key($action, $subject, $record->getKey())]
                        = "{$action}: {$record->entityPermissionLabel()}";
                }
            }
        }

        return $groups;
    }

    /**
     * @param  Model&HasEntityPermissions  $record
     * @return list<string>
     */
    protected function keysFor(Model $record): array
    {
        return array_map(
            fn (string $action): string => self::key($action, $record::entityPermissionSubject(), $record->getKey()),
            $record::entityPermissionActions(),
        );
    }

    protected function guard(): string
    {
        return config('auth.defaults.guard', 'web');
    }
}
