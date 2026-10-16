<?php

namespace Platform\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Platform\Views\ViewCount;
use Platform\Views\ViewCounter;

/**
 * View counter (P-17, FR-CNT-C08, SAD Part A §6): the website calls $record->countView() on
 * the record's page; admin lists sort by withViewTotal() / mostViewed(). The total comes
 * from core_view_counts, so it lags the cache by up to five minutes.
 */
trait HasViewCount
{
    public static function bootHasViewCount(): void
    {
        $forget = fn (Model $model) => app(ViewCounter::class)->forget($model);

        method_exists(static::class, 'forceDeleted') ? static::forceDeleted($forget) : static::deleted($forget);
    }

    public function viewCount(): MorphOne
    {
        return $this->morphOne(ViewCount::class, 'viewable');
    }

    public function countView(): void
    {
        app(ViewCounter::class)->hit($this);
    }

    public function viewTotal(): int
    {
        return (int) ($this->attributes['view_total'] ?? $this->viewCount?->total ?? 0);
    }

    /** Adds view_total to each row (0 when never viewed). */
    public function scopeWithViewTotal(Builder $query): Builder
    {
        $counts = (new ViewCount)->getTable();

        return $query->addSelect(['view_total' => ViewCount::query()->selectRaw('COALESCE(MAX(total), 0)')
            ->whereColumn("{$counts}.viewable_id", $this->getQualifiedKeyName())
            ->where("{$counts}.viewable_type", $this->getMorphClass())]);
    }

    public function scopeMostViewed(Builder $query): Builder
    {
        if ($query->getQuery()->columns === null) {
            $query->select($this->qualifyColumn('*'));
        }

        return $query->withViewTotal()->orderByDesc('view_total');
    }
}
