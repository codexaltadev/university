<?php

namespace Modules\Seo\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Platform\Models\LogModel;

/**
 * One thing visitors searched on the website, how often, and how many results it gave the last time
 * (seo_search_terms, Phase 4.7g). Not searched for 180 days: it goes.
 */
class SearchTerm extends LogModel
{
    public const KEEP_DAYS = 180;

    /** "Popular searches" come from the last 90 days */
    public const POPULAR_DAYS = 90;

    public $timestamps = false;

    protected $table = 'seo_search_terms';

    protected $fillable = ['term', 'term_hash', 'hits', 'last_results', 'first_searched_at', 'last_searched_at'];

    protected function casts(): array
    {
        return ['hits' => 'integer', 'last_results' => 'integer', 'first_searched_at' => 'datetime', 'last_searched_at' => 'datetime'];
    }

    public static function normalize(string $query): string
    {
        return Str::limit(Str::lower(Str::squish($query)), 191, '');
    }

    /** Count a search. */
    public static function record(string $query, int $results): void
    {
        $term = static::normalize($query);
        if ($term === '') {
            return;
        }
        $hash = hash('sha256', $term);

        $updated = static::query()->where('term_hash', $hash)->update([
            'hits' => DB::raw('hits + 1'),
            'last_results' => $results,
            'last_searched_at' => now(),
        ]);

        if ($updated === 0) {
            static::query()->insertOrIgnore([
                'term' => $term, 'term_hash' => $hash, 'hits' => 1, 'last_results' => $results,
                'first_searched_at' => now(), 'last_searched_at' => now(),
            ]);
        }
    }

    /** @return Collection<int, string> the most searched terms that found something lately */
    public static function popular(int $limit): Collection
    {
        return static::query()->where('last_results', '>', 0)->where('last_searched_at', '>=', now()->subDays(self::POPULAR_DAYS))
            ->orderByDesc('hits')->orderByDesc('last_searched_at')->limit($limit)->pluck('term');
    }

    /** Not searched for 180 days (DB-02 §9.4). */
    public static function clean(): int
    {
        return static::query()->where('last_searched_at', '<', now()->subDays(self::KEEP_DAYS))->delete();
    }
}
