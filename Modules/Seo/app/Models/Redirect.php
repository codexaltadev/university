<?php

namespace Modules\Seo\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Seo\Support\Redirects;
use Platform\Models\BaseModel;

/**
 * A redirect from an old address (seo_redirects, FR-SEO-04): to an external URL,
 * a path, or an internal record whose current address is used (P-15) — so a
 * later slug change never breaks it. Source is stored normalized with its hash.
 */
class Redirect extends BaseModel
{
    public const STATUS_CODES = [301, 302];

    public const SOURCES = ['manual', 'import', '404', 'slug'];

    public static string $titleAttribute = 'source_path';

    protected $table = 'seo_redirects';

    protected $fillable = ['source_path', 'destination_url', 'target_type', 'target_id', 'status_code', 'source', 'is_active'];

    protected function casts(): array
    {
        return ['status_code' => 'integer', 'hits' => 'integer', 'last_hit_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (Redirect $redirect): void {
            $redirect->source_path = Redirects::normalize($redirect->source_path);
            $redirect->source_hash = Redirects::hash($redirect->source_path);
        });

        static::saved(fn () => Redirects::flush());
        static::deleted(fn () => Redirects::flush());
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    /** Where it sends visitors now; null when its record is gone or hidden. */
    public function destination(): ?string
    {
        if ($this->target_type) {
            $target = $this->target;

            return $target && method_exists($target, 'url') && (! method_exists($target, 'isFrontendVisible') || $target->isFrontendVisible())
                ? $target->url()
                : null;
        }

        return $this->destination_url ?: null;
    }
}
