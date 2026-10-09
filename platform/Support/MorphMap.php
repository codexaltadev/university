<?php

namespace Platform\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Platform\Assignments\Assignment;
use Platform\Assignments\ManualPerson;
use Platform\Localization\Language;
use Platform\Localization\Translation;
use Platform\Media\MediaFolder;
use Platform\Media\MediaItem;
use Platform\Modules\ModuleRegistry;
use Platform\Settings\Setting;
use Platform\Statistics\StatItem;
use Platform\Statistics\StatSet;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabulary;
use Platform\Theme\Theme;

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
        'core.setting' => Setting::class,          // activity log subjects
        'core.language' => Language::class,
        'core.translation' => Translation::class,
        'core.vocabulary' => Vocabulary::class,
        'core.term' => Term::class,
        'core.media-item' => MediaItem::class,   // also spatie media.model_type (DB-02 §3.3)
        'core.media-folder' => MediaFolder::class,
        'core.stat-set' => StatSet::class,
        'core.stat-item' => StatItem::class,
        'core.theme' => Theme::class,            // activity log subject (theme activated)
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
