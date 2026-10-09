<?php

namespace Platform\Assignments;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Platform\Registry\Registry;
use Platform\Support\MorphMap;

/**
 * Standard Role Catalog (DB-01 §8.4, FR-REL-11). Seeded from assignable()
 * registrations: new keys are added, existing labels are never touched.
 */
class RoleCatalog
{
    public function __construct(protected Registry $registry) {}

    /** @return list<string> keys added */
    public function sync(?string $module = null): array
    {
        $added = [];
        $entries = $module === null
            ? $this->registry->all('assignable')
            : ($this->registry->forModule($module)['assignable'] ?? []);

        foreach ($entries as $entry) {
            $type = MorphMap::alias($entry['model']);

            foreach (array_values($entry['roles']) as $position => $key) {
                if (! preg_match('/^[a-z][a-z0-9_]*$/', $key)) {
                    throw new InvalidArgumentException("Role key \"{$key}\" must be snake_case.");
                }

                [$label, $single] = config("platform.role_catalog.{$key}", [Str::headline($key), false]);

                $role = AssignmentRole::query()->firstOrCreate(
                    ['assignable_type' => $type, 'role_key' => $key],
                    ['label' => ['en' => $label], 'is_single_holder' => $single, 'sort_order' => ($position + 1) * 10],
                );

                if ($role->wasRecentlyCreated) {
                    $added[] = "{$type}:{$key}";
                }
            }
        }

        return $added;
    }

    public function find(string $assignableType, string $key): ?AssignmentRole
    {
        return AssignmentRole::query()->where('assignable_type', $assignableType)->where('role_key', $key)->first();
    }

    public function label(string $assignableType, string $key): string
    {
        return (string) ($this->find($assignableType, $key)?->label ?? Str::headline($key));
    }
}
