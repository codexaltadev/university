<?php

namespace Platform\Scope;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Platform\Concerns\HasOwner;
use Platform\Concerns\IsAttachable;
use Platform\Concerns\IsRelationTarget;

/**
 * Global scope behind AppliesUserScope (FR-GEN-52, SAD Part B §5.3): admin lists
 * and queries only return records inside the signed-in user's scope.
 *
 * Applies while the admin panel is serving a request (not on the public website,
 * where scope has no meaning). Code can force it with UserScopeFilter::enforce().
 */
class UserScopeFilter implements Scope
{
    protected static bool $forced = false;

    /** Apply the filter outside the panel too (tests, exports run for a user). */
    public static function enforce(bool $on = true): void
    {
        static::$forced = $on;
    }

    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user || ! (static::$forced || Filament::isServing())) {
            return;
        }

        if ($user->hasRole(config('filament-shield.super_admin.name'))) {
            return;
        }

        $ids = app(ScopeResolver::class)->idsByType($user);

        if ($ids === null) {
            return;   // unscoped user (BRL-ROLE-03)
        }

        match (true) {
            is_array($columns = static::pathOf($model)) => $this->byColumns($builder, $model, $columns, $ids),
            $columns === 'self' => $builder->whereKey($ids[$model->getMorphClass()] ?? []),
            $columns === 'owner' => $this->byOwner($builder, $model, $ids),
            $columns === 'relation' => $builder->shownIn(array_merge(...array_map(
                fn (string $type, array $list): array => array_map(fn (int $id): string => "{$type}:{$id}", $list),
                array_keys($ids), $ids,
            ))),
            default => null,   // "none": permission only
        };
    }

    /**
     * Scope path of a model (§5.3): "owner", "self", "relation", column map or "none".
     *
     * @return string|array<string, string>
     */
    public static function pathOf(Model $model): string|array
    {
        if (property_exists($model, 'scopeColumns')) {
            return $model->scopeColumns;   // ['department_id' => 'department.department']
        }

        $traits = class_uses_recursive($model);

        return match (true) {
            in_array(HasOwner::class, $traits, true) => 'owner',
            in_array(IsRelationTarget::class, $traits, true) => 'self',
            in_array(IsAttachable::class, $traits, true) => 'relation',
            default => 'none',
        };
    }

    /**
     * HasOwner: records owned by an entity of the scope; main-site records are
     * the Admin's (P-04 … P-08).
     *
     * @param  array<string, list<int>>  $ids
     */
    protected function byOwner(Builder $builder, Model $model, array $ids): void
    {
        $builder->where(function (Builder $q) use ($ids, $model): void {
            foreach ($ids as $type => $list) {
                $q->orWhere(fn (Builder $o) => $o->where($model->qualifyColumn('owner_type'), $type)->whereIn($model->qualifyColumn('owner_id'), $list));
            }

            if ($ids === []) {
                $q->whereRaw('1 = 0');
            }
        });
    }

    /**
     * @param  array<string, string>  $columns  column => target type
     * @param  array<string, list<int>>  $ids
     */
    protected function byColumns(Builder $builder, Model $model, array $columns, array $ids): void
    {
        $builder->where(function (Builder $q) use ($columns, $ids, $model): void {
            foreach ($columns as $column => $type) {
                $q->orWhereIn($model->qualifyColumn($column), $ids[$type] ?? []);
            }
        });
    }
}
