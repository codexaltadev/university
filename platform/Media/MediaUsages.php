<?php

namespace Platform\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Keeps core_media_usages and core_media_items.usage_count in step (FR-MEDIA-09/15).
 * Used by HasMediaUsage, MediaPicker and settings that hold media.
 */
class MediaUsages
{
    /**
     * The media of one field of a record, in this order.
     *
     * @param  list<int|string|null>  $ids
     * @param  array<int, array<string, mixed>>|null  $meta  media id => this use's own data (an attachment's title …); null = leave as it is
     */
    public function sync(Model $usable, string $field, array $ids, ?array $meta = null): void
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($ids))));
        $type = $usable->getMorphClass();
        $key = $usable->getKey();

        DB::transaction(function () use ($ids, $type, $key, $field, $meta): void {
            $before = MediaUsage::query()->where(['usable_type' => $type, 'usable_id' => $key, 'field' => $field])->pluck('media_item_id')->all();

            MediaUsage::query()->where(['usable_type' => $type, 'usable_id' => $key, 'field' => $field])
                ->whereNotIn('media_item_id', $ids)->delete();

            foreach ($ids as $order => $id) {
                MediaUsage::query()->updateOrCreate(
                    ['usable_type' => $type, 'usable_id' => $key, 'field' => $field, 'media_item_id' => $id],
                    ['sort_order' => $order, ...($meta === null ? [] : ['meta' => ($meta[$id] ?? null) ?: null])],
                );
            }

            $this->recount([...$before, ...$ids]);
        });
    }

    /** Every usage of a record (record force-deleted). */
    public function clear(Model $usable): void
    {
        $this->forget(MediaUsage::query()->where(['usable_type' => $usable->getMorphClass(), 'usable_id' => $usable->getKey()]));
    }

    /** @param list<string> $types morph aliases (module uninstalled with its data) */
    public function forgetTypes(array $types): void
    {
        $this->forget(MediaUsage::query()->whereIn('usable_type', $types));
    }

    /** @param list<int> $itemIds */
    public function recount(array $itemIds): void
    {
        $itemIds = array_values(array_unique($itemIds));

        if ($itemIds === []) {
            return;
        }

        // Query-builder update: no activity log entry for bookkeeping
        DB::table('core_media_items')->whereIn('id', $itemIds)->update([
            'usage_count' => DB::raw('(SELECT COUNT(*) FROM core_media_usages WHERE core_media_usages.media_item_id = core_media_items.id)'),
        ]);
    }

    protected function forget($query): void
    {
        $items = (clone $query)->pluck('media_item_id')->all();
        $query->delete();
        $this->recount($items);
    }
}
