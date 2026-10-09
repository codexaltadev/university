<?php

namespace Modules\Seo\Models;

use Illuminate\Support\Facades\DB;
use Modules\Seo\Support\Redirects;
use Platform\Models\LogModel;

/**
 * One address that answered 404, with how often (seo_not_found_logs, FR-SEO-06).
 * Resolved once a redirect is made for it; resolved rows go after 90 days.
 */
class NotFoundLog extends LogModel
{
    public const KEEP_RESOLVED_DAYS = 90;

    public $timestamps = false;

    protected $table = 'seo_not_found_logs';

    protected $fillable = ['path', 'path_hash', 'hits', 'last_referrer', 'first_seen_at', 'last_seen_at', 'is_resolved'];

    protected function casts(): array
    {
        return ['hits' => 'integer', 'first_seen_at' => 'datetime', 'last_seen_at' => 'datetime', 'is_resolved' => 'boolean'];
    }

    /** Count a miss; a resolved address that misses again is open again. */
    public static function record(string $path, ?string $referrer): void
    {
        $path = Redirects::normalize($path);
        $hash = Redirects::hash($path);
        $referrer = $referrer ? mb_substr($referrer, 0, 2048) : null;

        $updated = static::query()->where('path_hash', $hash)->update([
            'hits' => DB::raw('hits + 1'),
            'last_seen_at' => now(),
            'last_referrer' => $referrer,
            'is_resolved' => false,
        ]);

        if ($updated === 0) {
            static::query()->insertOrIgnore([
                'path' => $path, 'path_hash' => $hash, 'hits' => 1, 'last_referrer' => $referrer,
                'first_seen_at' => now(), 'last_seen_at' => now(), 'is_resolved' => false,
            ]);
        }
    }

    public static function resolve(string $path): void
    {
        static::query()->where('path_hash', Redirects::hash(Redirects::normalize($path)))->update(['is_resolved' => true]);
    }

    /** Resolved rows not seen for 90 days (DB-02 §9.3). */
    public static function clean(): int
    {
        return static::query()->where('is_resolved', true)->where('last_seen_at', '<', now()->subDays(self::KEEP_RESOLVED_DAYS))->delete();
    }
}
