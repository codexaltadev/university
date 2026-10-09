<?php

namespace Platform\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * sort_order for drag & drop lists (FR-GEN-14). New records go to the end.
 */
trait HasSortOrder
{
    public static function bootHasSortOrder(): void
    {
        static::creating(function (Model $model): void {
            if ($model->sort_order === null) {
                $model->sort_order = (int) static::withoutGlobalScopes()->max('sort_order') + 1;
            }
        });
    }

    public function initializeHasSortOrder(): void
    {
        $this->mergeCasts(['sort_order' => 'integer']);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy($this->qualifyColumn('sort_order'))->orderBy($this->getQualifiedKeyName());
    }
}
