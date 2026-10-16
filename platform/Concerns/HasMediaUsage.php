<?php

namespace Platform\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Platform\Media\MediaItem;
use Platform\Media\MediaUsage;
use Platform\Media\MediaUsages;

/**
 * Files from the Media Library on any model (C-07, core_media_usages P-13). The
 * record never stores a file; it points to media items per field:
 *
 *     MediaPicker::make('featured_image')->image()     // form
 *     $post->firstMedia('featured_image')?->url('large')
 *     $post->mediaIn('gallery')
 *
 * Usage is shown on the media item and protects it from silent deletion (FR-MEDIA-09/10).
 */
trait HasMediaUsage
{
    public static function bootHasMediaUsage(): void
    {
        $event = method_exists(static::class, 'forceDeleted') ? 'forceDeleted' : 'deleted';

        static::$event(fn (Model $model) => app(MediaUsages::class)->clear($model));
    }

    public function mediaUsages(): MorphMany
    {
        return $this->morphMany(MediaUsage::class, 'usable');
    }

    /** @return Collection<int, MediaItem> active items of a field, in order */
    public function mediaIn(string $field): Collection
    {
        $ids = $this->mediaUsages()->where('field', $field)->orderBy('sort_order')->pluck('media_item_id')->all();

        return MediaItem::query()->whereIn('id', $ids)->get()->sortBy(fn (MediaItem $item) => array_search($item->id, $ids, true))->values();
    }

    /**
     * The uses of a field, in order, with their item and own data (an attachment's title is
     * $usage->meta['title'][locale]) — only uses whose item still exists.
     *
     * @return Collection<int, MediaUsage>
     */
    public function mediaUsagesIn(string $field): Collection
    {
        return $this->mediaUsages()->where('field', $field)->orderBy('sort_order')->with('item')->get()
            ->filter(fn (MediaUsage $usage): bool => $usage->item !== null)->values();
    }

    public function firstMedia(string $field): ?MediaItem
    {
        return $this->mediaIn($field)->first();
    }

    /** @return list<int> */
    public function mediaIdsIn(string $field): array
    {
        return $this->mediaUsages()->where('field', $field)->orderBy('sort_order')->pluck('media_item_id')->all();
    }

    /**
     * @param  list<int|string|null>  $ids
     * @param  array<int, array<string, mixed>>|null  $meta  media id => this use's own data
     */
    public function syncMedia(string $field, array $ids, ?array $meta = null): void
    {
        app(MediaUsages::class)->sync($this, $field, $ids, $meta);
    }
}
