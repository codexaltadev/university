<?php

namespace Platform\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * is_active flag (FR-GEN-04). Inactive records stay in the admin, never on the website.
 */
trait HasActiveFlag
{
    public function initializeHasActiveFlag(): void
    {
        $this->mergeCasts(['is_active' => 'boolean']);
        $this->attributes['is_active'] ??= true;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('is_active'), true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('is_active'), false);
    }

    public function activate(): bool
    {
        return $this->forceFill(['is_active' => true])->save();
    }

    public function deactivate(): bool
    {
        return $this->forceFill(['is_active' => false])->save();
    }
}
