<?php

namespace Platform\Concerns;

use Illuminate\Database\Eloquent\Model;
use Platform\Exceptions\ParentHasActiveChildren;

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

        foreach ($this->guardedChildren ?? [] as $relation) {
            $query = $this->{$relation}();
            $related = $query->getRelated();

            if (in_array(HasActiveFlag::class, class_uses_recursive($related), true)) {
                $query->where($related->qualifyColumn('is_active'), true);
            }

            $titles = $query->limit(20)->get()
                ->map(fn (Model $child): string => (string) ($child->getAttribute($child::$titleAttribute ?? 'name') ?? '#'.$child->getKey()))
                ->all();

            if ($titles !== []) {
                $found[$relation] = $titles;
            }
        }

        return $found;
    }
}
