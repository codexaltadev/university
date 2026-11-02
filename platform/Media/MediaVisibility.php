<?php

namespace Platform\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Platform\Concerns\HasExpiry;
use Platform\Models\BaseModel;
use Platform\Support\PublishDate;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

/**
 * A file follows the records that use it (owner 2026-11-02): when every record using a Media Library item is
 * off — inactive, deleted, not active yet or expired (BaseModel::activeNow) — or the item itself is, the file and
 * its sizes leave the public disk, so its address no longer opens; when one comes back on, the file returns to the
 * same address. Items nobody uses (the library itself, pictures written into rich text) keep their state; items
 * something other than a record uses (settings) and private items are left alone. Moving never stops the save that
 * caused it: a failure is logged and `uwms:media:visibility` puts it right.
 */
class MediaVisibility
{
    /** Where a withheld file waits: not served by the web server (private items live there too, each in its own folder) */
    public const WITHHELD_DISK = 'local';

    /** The items a record uses, after it was switched, deleted or restored */
    public function refreshFor(Model $usable): void
    {
        $this->refresh(MediaUsage::query()->where(['usable_type' => $usable->getMorphClass(), 'usable_id' => $usable->getKey()])->pluck('media_item_id')->all());
    }

    /** @var array<int, true> items waiting for the open transaction to commit (static: app() gives a new instance each time) */
    protected static array $pending = [];

    /**
     * Withhold or release each item's file by whether something switched on uses it — inside a transaction once
     * it commits (a rolled back save must not have moved files), each item once.
     *
     * @param  list<int>  $itemIds
     * @return array{withheld: int, released: int} what moved now (nothing yet inside a transaction)
     */
    public function refresh(array $itemIds): array
    {
        if (DB::transactionLevel() === 0) {
            return $this->apply($itemIds);
        }

        foreach ($itemIds as $id) {
            self::$pending[(int) $id] = true;
        }
        // One callback per call: the first to run takes them all (a rolled back transaction drops its callbacks, not the list)
        DB::afterCommit(function (): void {
            $ids = array_keys(self::$pending);
            self::$pending = [];
            $this->apply($ids);
        });

        return ['withheld' => 0, 'released' => 0];
    }

    /**
     * @param  list<int>  $itemIds
     * @return array{withheld: int, released: int}
     */
    protected function apply(array $itemIds): array
    {
        $moved = ['withheld' => 0, 'released' => 0];
        $itemIds = array_values(array_unique(array_map('intval', $itemIds)));
        if ($itemIds === []) {
            return $moved;
        }

        foreach (MediaItem::query()->withoutGlobalScopes()->whereKey($itemIds)->where('is_private', false)->get() as $item) {
            $shown = $this->shown($item);
            if ($shown === null) {
                continue;
            }
            $file = Media::query()->where('model_type', $item->getMorphClass())->where('model_id', $item->getKey())->where('collection_name', MediaItem::COLLECTION)->first();
            if ($file === null) {
                continue;
            }
            $public = (string) config('media-library.disk_name', 'public');
            $to = $shown ? $public : self::WITHHELD_DISK;
            if ($file->disk === $to) {
                continue;
            }
            if ($this->move($file, $to)) {
                $moved[$shown ? 'released' : 'withheld']++;
            }
        }

        return $moved;
    }

    /**
     * Should the item's file be public: false when the item is off or everything using it is off; null when
     * nothing decides it (no usages: the library's own file, perhaps written into rich text — left as it is).
     */
    public function shown(MediaItem $item): ?bool
    {
        if (! $item->is_active || $item->trashed()) {
            return false;
        }

        $usages = MediaUsage::query()->where('media_item_id', $item->getKey())->get(['usable_type', 'usable_id']);
        if ($usages->isEmpty()) {
            return null;
        }

        foreach ($usages->groupBy('usable_type') as $type => $uses) {
            $class = Relation::getMorphedModel($type) ?? $type;
            if (! class_exists($class) || ! is_subclass_of($class, BaseModel::class)) {
                return true;   // a setting, something without a switch: shown
            }
            if ($class::query()->withoutGlobalScopes()->activeNow()->whereKey($uses->pluck('usable_id')->all())->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Records whose active_at / published_at / expires_at passed since then switched on or off without being saved:
     * their items (the scheduled `uwms:media:visibility --since`).
     */
    public function refreshScheduled(Carbon $since): array
    {
        $ids = [];
        foreach (MediaUsage::query()->distinct()->pluck('usable_type') as $type) {
            $class = Relation::getMorphedModel($type) ?? $type;
            if (! class_exists($class) || ! is_subclass_of($class, BaseModel::class)) {
                continue;
            }
            $columns = array_filter([PublishDate::column($class), in_array(HasExpiry::class, class_uses_recursive($class), true) ? 'expires_at' : null]);
            foreach ($columns as $column) {
                $records = $class::query()->withoutGlobalScopes()->whereBetween($column, [$since, now()])->pluck((new $class)->getKeyName())->all();
                if ($records !== []) {
                    array_push($ids, ...MediaUsage::query()->where('usable_type', $type)->whereIn('usable_id', $records)->pluck('media_item_id')->all());
                }
            }
        }

        return $this->refresh($ids);
    }

    /** Every item that is used or switched off (`uwms:media:visibility` without --since: after a migration, a restore …) */
    public function refreshAll(): array
    {
        $moved = ['withheld' => 0, 'released' => 0];
        MediaItem::query()->withoutGlobalScopes()->where('is_private', false)
            ->where(fn ($q) => $q->where('usage_count', '>', 0)->orWhere('is_active', false)->orWhereNotNull('deleted_at'))
            ->select('id')->chunkById(500, function ($items) use (&$moved): void {
                foreach ($this->refresh($items->pluck('id')->all()) as $key => $count) {
                    $moved[$key] += $count;
                }
            });

        return $moved;
    }

    /** The file's folder (the original, its sizes) to the other disk, at the same path; the media row follows. */
    protected function move(Media $file, string $to): bool
    {
        $from = $file->disk;
        $folder = (string) $file->getKey();   // spatie's default path: {media id}/, conversions/, responsive-images/

        try {
            $source = Storage::disk($from);
            $target = Storage::disk($to);
            $local = config("filesystems.disks.{$from}.driver") === 'local' && config("filesystems.disks.{$to}.driver") === 'local';
            if ($source->exists($folder)) {
                if ($local) {   // the same server: a rename, however large the file
                    File::ensureDirectoryExists(dirname($target->path($folder)));
                    if ($target->exists($folder)) {
                        $target->deleteDirectory($folder);
                    }
                    if (! @rename($source->path($folder), $target->path($folder))) {
                        throw new \RuntimeException("could not move {$folder}");
                    }
                } else {
                    foreach ($source->allFiles($folder) as $path) {
                        $target->writeStream($path, $source->readStream($path));
                    }
                    $source->deleteDirectory($folder);
                }
            }
            // Bookkeeping only: no events, no activity log entry
            DB::table($file->getTable())->where('id', $file->getKey())->update(['disk' => $to, 'conversions_disk' => $to]);

            return true;
        } catch (Throwable $e) {
            Log::warning("Media file {$file->getKey()} not moved from {$from} to {$to}: {$e->getMessage()}");

            return false;
        }
    }
}
