<?php

namespace Platform\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Platform\Exceptions\ParentHasActiveChildren;
use Platform\Registry\Registry;

/**
 * Stops deleting a record that still has active children (FR-GEN-08),
 * e.g. a Faculty with active Departments. The model lists the child relations:
 *
 *     protected array $guardedChildren = ['departments'];
 */
trait GuardsParentDelete
{
    public static function bootGuardsParentDelete(): void
    {
        static::deleting(function (Model $model): void {
            $children = $model->activeChildren();

            if ($children !== []) {
                throw new ParentHasActiveChildren($model, $children);
            }
        });
    }

    /**
     * Active, not deleted children per relation, for the warning list.
     *
     * @return array<string, list<string>> relation => record titles
     */
    public function activeChildren(): array
    {
        $found = [];
        $covered = [];

        foreach ($this->guardedChildren ?? [] as $relation) {
            $query = $this->{$relation}();
            $related = $query->getRelated();
            $covered[] = $related::class;

            if (in_array(HasActiveFlag::class, class_uses_recursive($related), true)) {
                $query->where($related->qualifyColumn('is_active'), true);
            }

            $titles = $query->limit(20)->get()
                ->map(fn (Model $child): string => static::childTitle($child))
                ->all();

            if ($titles !== []) {
                $found[$relation] = $titles;
            }
        }

        // Children registered by other modules: $r->relationTarget(Department::class, parent: Faculty::class, parentKey: 'faculty_id')
        foreach (app(Registry::class)->all('relationTarget') as $entry) {
            if (($entry['parent'] ?? null) !== static::class || blank($entry['parentKey'] ?? null)
                || in_array($entry['model'], $covered, true) || ! class_exists($entry['model'])) {
                continue;
            }

            $child = new $entry['model'];
            $query = $child->newQuery()->withoutGlobalScopes()->where($entry['parentKey'], $this->getKey());

            if (in_array(SoftDeletes::class, class_uses_recursive($child), true)) {
                $query->whereNull($child->qualifyColumn($child->getDeletedAtColumn()));
            }

            if (in_array(HasActiveFlag::class, class_uses_recursive($child), true)) {
                $query->where($child->qualifyColumn('is_active'), true);
            }

            $titles = $query->limit(20)->get()
                ->map(fn (Model $c): string => static::childTitle($c))
                ->all();

            if ($titles !== []) {
                $found[Str::plural(Str::camel(class_basename($entry['model'])))] = $titles;
            }
        }

        return $found;
    }

    protected static function childTitle(Model $child): string
    {
        $attribute = $child::$titleAttribute ?? 'name';
        $value = method_exists($child, 'getTranslation') && in_array($attribute, $child->translatable ?? [], true)
            ? $child->getTranslation($attribute, app()->getLocale())
            : $child->getAttribute($attribute);

        return (string) ($value ?? '#'.$child->getKey());
    }
}
