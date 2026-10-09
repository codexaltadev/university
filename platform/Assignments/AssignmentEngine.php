<?php

namespace Platform\Assignments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Platform\Contracts\PersonSource;
use Platform\Modules\ModuleRegistry;
use Platform\Registry\Registry;
use Platform\Support\MorphMap;

/**
 * Person Assignment Engine (SAD Part B §4, FR-REL-09/10): one person record,
 * many roles in many places.
 */
class AssignmentEngine
{
    public function __construct(
        protected Registry $registry,
        protected RoleCatalog $roles,
        protected ModuleRegistry $modules,
    ) {}

    /**
     * Put a person in a role.
     *
     * For a single-holder role (Dean, Head ...) the current holders are returned
     * so the UI can propose ending them; with $endPrevious they are ended the day
     * before the new term starts (they move to "Former").
     *
     * @param  array<string, mixed>  $attributes  starts_on, ends_on, sort_order, is_public, role_label, overrides, note
     * @return array{assignment: Assignment, previous: Collection<int, Assignment>}
     */
    public function assign(Model $person, Model $assignable, string $roleKey, array $attributes = [], bool $endPrevious = false): array
    {
        if (! $person instanceof PersonSource || ! $this->isRegistered('personSource', $person) && ! $person instanceof ManualPerson) {
            throw new InvalidArgumentException(class_basename($person).' is not a registered person source.');
        }

        if (! $this->isRegistered('assignable', $assignable)) {
            throw new InvalidArgumentException(class_basename($assignable).' is not registered as assignable.');
        }

        $role = $this->roles->find($assignable->getMorphClass(), $roleKey);

        if (! $role || ! $role->is_active) {
            throw new InvalidArgumentException("\"{$roleKey}\" is not an active role for ".class_basename($assignable).'.');
        }

        $previous = $role->is_single_holder
            ? $this->query($assignable, $roleKey)->current()->get()
            : collect();

        if ($endPrevious && $previous->isNotEmpty()) {
            $endsOn = Carbon::parse($attributes['starts_on'] ?? now())->subDay()->toDateString();
            $previous->each(fn (Assignment $a) => $a->update(['ends_on' => $endsOn]));
        }

        $assignment = Assignment::create([
            'person_type' => $person->getMorphClass(),
            'person_id' => $person->getKey(),
            'assignable_type' => $assignable->getMorphClass(),
            'assignable_id' => $assignable->getKey(),
            'role_key' => $roleKey,
            'sort_order' => $attributes['sort_order'] ?? ((int) $this->query($assignable, $roleKey)->max('sort_order') + 1),
            ...array_intersect_key($attributes, array_flip(['starts_on', 'ends_on', 'is_public', 'role_label', 'overrides', 'note'])),
        ]);

        return ['assignment' => $assignment, 'previous' => $previous];
    }

    /**
     * People of a place for the website (§4.4): visible assignments only, persons
     * that are active and whose source module is active, in catalog role order
     * then assignment order.
     *
     * @param  string|list<string>|null  $roles
     * @return Collection<int, PersonView>
     */
    public function people(Model $assignable, string|array|null $roles = null, bool $former = false): Collection
    {
        $query = $this->query($assignable, $roles)
            ->where('is_active', true)
            ->where('is_public', true)
            ->with('person');

        $former ? $query->former() : $query->current();

        $order = AssignmentRole::query()
            ->where('assignable_type', $assignable->getMorphClass())
            ->pluck('sort_order', 'role_key');

        return $query->get()
            ->filter(fn (Assignment $a): bool => $a->person instanceof PersonSource
                && $a->person->personPresenter()->isActive()
                && $this->sourceModuleActive($a->person))
            ->sortBy([
                fn (Assignment $a, Assignment $b) => ($order[$a->role_key] ?? PHP_INT_MAX) <=> ($order[$b->role_key] ?? PHP_INT_MAX),
                fn (Assignment $a, Assignment $b) => $a->sort_order <=> $b->sort_order,
            ])
            ->map(fn (Assignment $a): PersonView => new PersonView(
                $a,
                $a->person->personPresenter(),
                (string) ($a->role_label ?: $this->roles->label($a->assignable_type, $a->role_key)),
            ))
            ->values();
    }

    /** Everything a person holds (profile page, cleanup). */
    public function assignmentsOf(Model $person): Collection
    {
        return Assignment::query()
            ->where('person_type', $person->getMorphClass())
            ->where('person_id', $person->getKey())
            ->get();
    }

    /** Person or assignable permanently deleted (no FK on polymorphic references). */
    public function forget(Model $model): void
    {
        Assignment::withTrashed()
            ->where(fn ($q) => $q
                ->where(fn ($p) => $p->where('person_type', $model->getMorphClass())->where('person_id', $model->getKey()))
                ->orWhere(fn ($t) => $t->where('assignable_type', $model->getMorphClass())->where('assignable_id', $model->getKey())))
            ->forceDelete();
    }

    /** Module uninstalled with its data. */
    public function forgetTypes(array $types): void
    {
        Assignment::withTrashed()->whereIn('person_type', $types)->orWhereIn('assignable_type', $types)->forceDelete();
        AssignmentRole::query()->whereIn('assignable_type', $types)->delete();
    }

    /** @param string|list<string>|null $roles */
    protected function query(Model $assignable, string|array|null $roles = null)
    {
        return Assignment::query()
            ->where('assignable_type', $assignable->getMorphClass())
            ->where('assignable_id', $assignable->getKey())
            ->when($roles !== null, fn ($q) => $q->whereIn('role_key', (array) $roles));
    }

    protected function isRegistered(string $kind, Model $model): bool
    {
        return collect($this->registry->all($kind))->contains(fn (array $e): bool => MorphMap::alias($e['model']) === $model->getMorphClass());
    }

    protected function sourceModuleActive(Model $person): bool
    {
        $slug = $this->modules->slugForClass($person::class);

        return $slug === null || $this->modules->isActive($slug);
    }
}
