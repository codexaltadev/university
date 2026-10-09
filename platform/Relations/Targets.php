<?php

namespace Platform\Relations;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Platform\Models\UserScope;
use Platform\Registry\Registry;
use Platform\Scope\ScopeResolver;
use Platform\Scope\UserScopeFilter;
use Platform\Support\MorphMap;

/**
 * What content can be shown in, for the "Show in" field (FR-GEN-50/51, FR-REL-03/05):
 * active records of the registered relation targets of active modules; a scoped
 * user only gets the entities of their scope, their own entities preselected.
 */
class Targets
{
    /** @var array<string, string> key => label, per request (list columns ask for the same places again and again) */
    protected array $labels = [];

    public function __construct(protected Registry $registry, protected ScopeResolver $scope) {}

    /** @return array<string, class-string<Model>> morph alias => model */
    public function types(): array
    {
        return collect($this->registry->all('relationTarget'))
            ->mapWithKeys(fn (array $entry): array => [MorphMap::alias($entry['model']) => $entry['model']])
            ->all();
    }

    /**
     * Choices grouped by kind: ["Department" => ["department.department:12" => "CSE"]].
     *
     * @return array<string, array<string, string>>
     */
    public function options(?User $user): array
    {
        $allowed = $user && ! $user->isSuperAdmin() ? $this->scope->keysFor($user) : null;
        $groups = [];

        foreach ($this->types() as $alias => $model) {
            $query = $model::query()->withoutGlobalScope(UserScopeFilter::class);
            $instance = new $model;

            if (Schema::hasColumn($instance->getTable(), 'is_active')) {
                $query->where($instance->qualifyColumn('is_active'), true);
            }

            if ($allowed !== null) {
                $ids = collect($allowed)->filter(fn (string $key): bool => str_starts_with($key, $alias.':'))
                    ->map(fn (string $key): int => (int) Str::after($key, ':'))->all();
                $query->whereKey($ids);
            }

            $items = $query->get()
                ->mapWithKeys(fn (Model $record): array => ["{$alias}:{$record->getKey()}" => $this->titleOf($record)])
                ->sort()
                ->all();

            if ($items !== []) {
                $groups[$this->typeLabel($model)] = $items;
            }
        }

        return $groups;
    }

    /** "Department: CSE" (or the key itself when the record is gone). */
    public function label(string $key): string
    {
        return $this->labels[$key] ??= $this->resolveLabel($key);
    }

    protected function resolveLabel(string $key): string
    {
        if ($key === RelationEngine::SITE_KEY) {
            return __('platform::relations.main_site');
        }

        [$alias, $id] = explode(':', $key, 2);
        $model = Relation::getMorphedModel($alias);
        $record = $model ? $model::query()->withoutGlobalScope(UserScopeFilter::class)->find($id) : null;

        return $record ? $this->typeLabel($model).': '.$this->titleOf($record) : $key;
    }

    /** @return list<string> a scoped user's own entities that content can be shown in (FR-GEN-51 default) */
    public function defaultFor(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $types = array_keys($this->types());

        return UserScope::query()->where('user_id', $user->id)->get()
            ->map(fn (UserScope $scope): string => $scope->key())
            ->filter(fn (string $key): bool => in_array(Str::before($key, ':'), $types, true))
            ->values()->all();
    }

    /** Context keys this user may highlight in (null = all); the main site needs HighlightMain. */
    public function highlightableFor(?User $user): ?array
    {
        return $user && ! $user->isSuperAdmin() ? $this->scope->keysFor($user) : null;
    }

    protected function typeLabel(string $model): string
    {
        return Str::headline(class_basename($model));
    }

    protected function titleOf(Model $record): string
    {
        return (string) ($record->getAttribute($record::$titleAttribute ?? 'name') ?? '#'.$record->getKey());
    }
}
