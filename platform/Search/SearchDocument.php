<?php

namespace Platform\Search;

use Illuminate\Database\Eloquent\Model;

/** One row of the search index (core_search_index, P-16). Written only by SearchIndex. */
class SearchDocument extends Model
{
    public const CREATED_AT = null;

    protected $table = 'core_search_index';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'weight' => 'integer'];
    }
}
