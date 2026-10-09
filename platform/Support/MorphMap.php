<?php

namespace Platform\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Platform\Assignments\Assignment;
use Platform\Assignments\ManualPerson;
use Platform\Modules\ModuleRegistry;

/**
 * Fixed aliases in polymorphic columns instead of class names (SAD Part B §1.3,
 * ADR-17): "{slug}.{model}". Aliases of inactive modules stay in the map so their
 * data can still be read and cleaned up.
 */
class MorphMap
{
    /** Kernel models that take part in polymorphic relations. */
    public const KERNEL = [
        'core.user' => User::class,
        'core.manual-person' => ManualPerson::class,
        'core.assignment' => Assignment::class,
    ];

    public static function register(ModuleRegistry $modules): void
    {
        $map = self::KERNEL;

        foreach ($modules->manifests() as $manifest) {
            $map += $manifest->morphAliases();
        }

        Relation::enforceMorphMap($map);
    }

    /** Alias of a model class or instance (e.g. "department.department"). */
    public static function alias(string|object $model): string
    {
        $class = is_object($model) ? $model::class : $model;

        return array_search($class, Relation::morphMap(), true) ?: $class;
    }
}
