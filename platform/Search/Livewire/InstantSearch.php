<?php

namespace Platform\Search\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Platform\Search\SearchIndex;

/**
 * Header search box (FR-SRCH-05): the top five results while typing, then "all
 * results" on the search page. On a microsite it can stay inside that entity
 * (FR-SRCH-06). The view is theme-overridable: theme::livewire.instant-search.
 *
 *   <livewire:platform.instant-search :scope="$context->isMicrosite() ? $context->key() : null" />
 */
class InstantSearch extends Component
{
    public const LIMIT = 5;

    public string $q = '';

    public ?string $scope = null;

    public function render(): View
    {
        $results = mb_strlen(trim($this->q)) >= 2
            ? app(SearchIndex::class)->search($this->q, ownerKey: $this->scope, perPage: self::LIMIT)
            : null;

        return view('theme::livewire.instant-search', [
            'results' => $results,
            'allUrl' => route('search', array_filter(['q' => $this->q, 'in' => $this->scope])),
        ]);
    }
}
