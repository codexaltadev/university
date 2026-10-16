<?php

namespace Platform\Support;

use Illuminate\Database\Eloquent\Model;
use Platform\Concerns\HasActiveAt;
use Platform\Concerns\HasPublishDate;

/**
 * Which column schedules a model (FR-GEN-10/11): published_at (HasPublishDate, older modules)
 * or active_at (HasActiveAt, new content modules), or none. The kernel's search index,
 * scheduled re-index, context ordering and Publishing panel read it here.
 */
final class PublishDate
{
    /** @param Model|class-string<Model>|null $model */
    public static function column(Model|string|null $model): ?string
    {
        if ($model === null || $model === '') {
            return null;
        }
        $traits = class_uses_recursive($model);

        return match (true) {
            in_array(HasActiveAt::class, $traits, true) => 'active_at',
            in_array(HasPublishDate::class, $traits, true) => 'published_at',
            default => null,
        };
    }
}
