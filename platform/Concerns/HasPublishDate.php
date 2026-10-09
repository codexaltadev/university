<?php

namespace Platform\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * published_at (FR-GEN-10/11, BRD Q-27): empty or past = published, future = scheduled.
 * No status column, no approval workflow.
 */
trait HasPublishDate
{
    public function initializeHasPublishDate(): void
    {
        $this->mergeCasts(['published_at' => 'datetime']);
    }

    /** Part of frontendVisible(). */
    protected function applyFrontendVisibilityHasPublishDate(Builder $query): void
    {
        $column = $this->qualifyColumn('published_at');

        $query->where(fn (Builder $q) => $q->whereNull($column)->orWhere($column, '<=', now()));
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('published_at'), '>', now());
    }

    public function isScheduled(): bool
    {
        return $this->published_at !== null && $this->published_at->isFuture();
    }
}
