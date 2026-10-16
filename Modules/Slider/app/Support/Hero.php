<?php

namespace Modules\Slider\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Slider\Models\Slide;
use Platform\Hierarchy\EntityHierarchy;
use Platform\Relations\RelationEngine;

/**
 * The hero of a place (FR-SLD-03/06): its hero slides in order; none → its page banner; none →
 * the same for each parent entity (CSE → FSIT → the main site is not used: a microsite without
 * any keeps the theme's default). The main site is "site:0".
 */
class Hero
{
    public function __construct(protected RelationEngine $engine, protected EntityHierarchy $hierarchy) {}

    /** @return Collection<int, Slide> */
    public function for(string $contextKey): Collection
    {
        $keys = [$contextKey];
        if ($contextKey !== RelationEngine::SITE_KEY) {
            $keys = [...$keys, ...$this->hierarchy->ancestors($contextKey)];   // nearest first
        }

        foreach ($keys as $key) {
            foreach (['hero', 'page_banner'] as $placement) {
                $slides = $this->slides($key, $placement)->get();
                if ($slides->isNotEmpty()) {
                    return $placement === 'page_banner' ? $slides->take(1) : $slides;
                }
            }
        }

        return (new Slide)->newCollection();
    }

    /** Visible slides of one place and placement, in their order — only those with a picture or video to show. */
    public function slides(string $contextKey, string $placement = 'hero'): Builder
    {
        return $this->engine->for($contextKey)->content(Slide::class)->query()
            ->where('placement', $placement)
            ->whereHas('mediaUsages', fn (Builder $u) => $u->whereIn('field', ['image_desktop', 'video']))
            ->reorder()->ordered();
    }
}
