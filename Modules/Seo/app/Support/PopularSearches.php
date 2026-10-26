<?php

namespace Modules\Seo\Support;

use Modules\Seo\Models\SearchTerm;
use Modules\Seo\Settings\SearchSettings;

/**
 * "Popular searches" for the search window and an empty search page (Phase 4.7g): the ones in Settings › Search, else the
 * most searched terms that found something lately; none when Settings turns them off. Given to the views as `$popular`.
 */
class PopularSearches
{
    /** @return list<array{label: string, url: string}> */
    public static function links(): array
    {
        if (! settings('search.show_popular', true)) {
            return [];
        }

        $entered = array_values(array_filter(array_map('trim', explode(',', (string) settings('search.popular')))));
        $terms = $entered !== [] ? $entered : SearchTerm::popular(SearchSettings::POPULAR)->all();

        return array_map(fn (string $t): array => ['label' => $t, 'url' => route('search', ['q' => $t])], array_slice($terms, 0, SearchSettings::POPULAR));
    }
}
