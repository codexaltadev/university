<?php

namespace Platform\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\Concerns\Blameable;
use Platform\Concerns\GuardsParentDelete;
use Platform\Concerns\HasActiveFlag;
use Platform\Concerns\LogsPlatformActivity;
use Platform\Modules\ModuleRegistry;

/**
 * Base of every business (B) model (SAD Part A §6.1–6.4).
 * Table must use $table->platformColumns().
 */
abstract class BaseModel extends Model
{
    use Blameable;
    use GuardsParentDelete;
    use HasActiveFlag;
    use LogsPlatformActivity;
    use SoftDeletes;

    /** Used in warnings and logs when a record is listed by name. */
    public static string $titleAttribute = 'name';

    /**
     * The only definition of "visible on the website" (FR-GEN-12):
     * active, not deleted, module active, plus each opt-in trait's rule
     * (HasPublishDate, HasExpiry ...). Never write these conditions by hand.
     */
    public function scopeFrontendVisible(Builder $query): Builder
    {
        if (! static::moduleIsActive()) {
            return $query->whereRaw('1 = 0');
        }

        $query->where($this->qualifyColumn('is_active'), true);

        foreach (class_uses_recursive(static::class) as $trait) {
            $method = 'applyFrontendVisibility'.class_basename($trait);

            if (method_exists($this, $method)) {
                $this->{$method}($query);
            }
        }

        return $query;
    }

    public function isFrontendVisible(): bool
    {
        return $this->exists
            && static::query()->frontendVisible()->whereKey($this->getKey())->exists();
    }

    /** Owning module's slug (null for kernel / app models). */
    public static function moduleSlug(): ?string
    {
        return app(ModuleRegistry::class)->slugForClass(static::class);
    }

    public static function moduleIsActive(): bool
    {
        $slug = static::moduleSlug();

        return $slug === null || app(ModuleRegistry::class)->isActive($slug);
    }
}
