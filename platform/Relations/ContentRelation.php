<?php

namespace Platform\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Platform\Models\BasePivot;

/**
 * content ↔ target link (core_relations, P-01, DB-01 §8.1).
 * Target ("site", 0) = the content is published on the main site.
 */
class ContentRelation extends BasePivot
{
    protected $table = 'core_relations';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_highlighted' => 'boolean',
            'highlight_until' => 'datetime',
        ];
    }

    public function content(): MorphTo
    {
        return $this->morphTo();
    }

    public function targetKey(): string
    {
        return $this->target_type.':'.$this->target_id;
    }

    public function isMainSite(): bool
    {
        return $this->target_type === RelationEngine::SITE_TYPE;
    }

    /** Highlighted and not expired (FR-CNT-H03). */
    public function scopeHighlightedNow(Builder $query): Builder
    {
        return $query->where('is_highlighted', true)
            ->where(fn (Builder $q) => $q->whereNull('highlight_until')->orWhere('highlight_until', '>', now()));
    }

    public function isHighlightedNow(): bool
    {
        return $this->is_highlighted && ($this->highlight_until === null || $this->highlight_until->isFuture());
    }
}
