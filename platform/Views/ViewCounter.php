<?php

namespace Platform\Views;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * View counter (P-17, FR-CNT-C08, DB-02 §12.3). A page view adds one in the cache; every
 * five minutes uwms:views:flush adds the gathered numbers to core_view_counts in one go.
 * Bots are not counted. A view landing between reading and clearing a counter during a
 * flush may be lost — acceptable for a view count.
 */
class ViewCounter
{
    protected const PREFIX = 'platform:views:';

    protected const PENDING = 'platform:views:pending';

    /** Crawlers, link previews and monitors (user agent, case-insensitive). */
    protected const BOTS = '/bot|crawl|spider|slurp|archiver|preview|facebookexternalhit|embedly|monitor|lighthouse|headless|curl|wget|python-requests|go-http-client/i';

    public function hit(Model $record, ?string $userAgent = null): void
    {
        $userAgent ??= (string) request()->userAgent();
        if ($userAgent === '' || preg_match(self::BOTS, $userAgent) || ! $record->exists) {
            return;
        }

        // add() first: the database store does not increment a missing key
        $key = $record->getMorphClass().'|'.$record->getKey();
        if ($this->cache()->add(self::PREFIX.$key, 1)) {
            $this->remember($key);
        } else {
            $this->cache()->increment(self::PREFIX.$key);
        }
    }

    /** Write the gathered views; returns how many records got views. */
    public function flush(): int
    {
        $keys = $this->cache()->lock(self::PENDING.':lock', 10)->block(5, function (): array {
            $keys = (array) $this->cache()->get(self::PENDING, []);
            $this->cache()->forget(self::PENDING);

            return $keys;
        });

        $now = Carbon::now();
        $written = 0;
        foreach (array_keys($keys) as $key) {
            $views = (int) $this->cache()->pull(self::PREFIX.$key, 0);
            if ($views < 1) {
                continue;
            }
            [$type, $id] = explode('|', $key, 2);
            $this->add($type, (int) $id, $views, $now);
            $written++;
        }

        return $written;
    }

    /** Start a record at a known total — legacy view counts (Data Migration Plan); does not lower one. */
    public function seed(Model $record, int $total): void
    {
        $current = $this->total($record);
        if ($total > $current) {
            $this->add($record->getMorphClass(), (int) $record->getKey(), $total - $current, null);
        }
    }

    public function total(Model $record): int
    {
        return (int) ViewCount::query()->where('viewable_type', $record->getMorphClass())->where('viewable_id', $record->getKey())->value('total');
    }

    public function forget(Model $record): void
    {
        ViewCount::query()->where('viewable_type', $record->getMorphClass())->where('viewable_id', $record->getKey())->delete();
    }

    /** @param list<string> $types morph aliases of an uninstalled module */
    public function forgetTypes(array $types): void
    {
        if ($types !== []) {
            ViewCount::query()->whereIn('viewable_type', $types)->delete();
        }
    }

    protected function add(string $type, int $id, int $views, ?Carbon $at): void
    {
        DB::table('core_view_counts')->upsert(
            [['viewable_type' => $type, 'viewable_id' => $id, 'total' => $views, 'last_viewed_at' => $at]],
            ['viewable_type', 'viewable_id'],
            ['total' => DB::raw('total + '.$views), 'last_viewed_at' => $at ?? DB::raw('last_viewed_at')],
        );
    }

    /** Keep the record's key in the pending list (one entry per record until the next flush). */
    protected function remember(string $key): void
    {
        $this->cache()->lock(self::PENDING.':lock', 10)->block(5, function () use ($key): void {
            $keys = (array) $this->cache()->get(self::PENDING, []);
            $keys[$key] = true;
            $this->cache()->forever(self::PENDING, $keys);
        });
    }

    protected function cache(): Repository
    {
        return Cache::store();
    }
}
