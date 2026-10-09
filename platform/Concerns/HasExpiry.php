<?php

namespace Platform\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * expires_at (FR-GEN-13) — only where expiry means something (Notice, Slider, Announcement).
 */
trait HasExpiry
{
    public function initializeHasExpiry(): void
    {
        $this->mergeCasts(['expires_at' => 'datetime']);
    }

    /** Part of frontendVisible(). */
    protected function applyFrontendVisibilityHasExpiry(Builder $query): void
    {
        $column = $this->qualifyColumn('expires_at');

        $query->where(fn (Builder $q) => $q->whereNull($column)->orWhere($column, '>', now()));
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('expires_at'), '<=', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
