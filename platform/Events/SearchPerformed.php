<?php

namespace Platform\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * A visitor searched the website on the search page (FR-SRCH-02): what they asked and how many results it gave, so a
 * module can learn what people look for and what they do not find (the SEO module's "Search terms"). Not sent while
 * typing in the header box, only for a search page's first page.
 */
class SearchPerformed
{
    use Dispatchable;

    public function __construct(public string $query, public int $total, public ?string $module = null, public ?string $scope = null) {}
}
