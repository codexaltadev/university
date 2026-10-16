<?php

namespace Platform\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * active_at (FR-GEN-10/11, owner 2026-10-10/11): the time a record starts to show — empty or past
 * = shown (when is_active), future = scheduled. New content modules (News, Notice, Events …) use
 * this instead of published_at; no status column, no approval workflow (BRD Q-27).
 */
trait HasActiveAt
{
    public function initializeHasActiveAt(): void
    {
        $this->mergeCasts(['active_at' => 'datetime']);
    }

    /** Part of frontendVisible(). */
    protected function applyFrontendVisibilityHasActiveAt(Builder $query): void
    {
        $column = $this->qualifyColumn('active_at');

        $query->where(fn (Builder $q) => $q->whereNull($column)->orWhere($column, '<=', now()));
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('active_at'), '>', now());
    }

    public function isScheduled(): bool
    {
        return $this->active_at !== null && $this->active_at->isFuture();
    }

    /** The date the website shows and sorts by: when it became visible, else when it was made. */
    public function shownAt(): ?Carbon
    {
        return $this->active_at ?? $this->created_at;
    }
}
