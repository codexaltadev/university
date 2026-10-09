<?php

namespace Platform\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Where a media item is used (core_media_usages, P-13). Written by MediaUsages only. */
class MediaUsage extends Model
{
    protected $table = 'core_media_usages';

    protected $fillable = ['media_item_id', 'usable_type', 'usable_id', 'field', 'sort_order', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'sort_order' => 'integer'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class, 'media_item_id');
    }

    public function usable(): MorphTo
    {
        return $this->morphTo()->withoutGlobalScopes();
    }
}
