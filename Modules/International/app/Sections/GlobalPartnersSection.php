<?php

namespace Modules\International\Sections;

use Illuminate\Support\Collection;
use Modules\International\Models\Institution;
use Modules\International\Support\InternationalWebsite;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Relations\RelationEngine;

/** Homepage section "Global Partners" (FR-INTL-04/09): a logo carousel, the featured (highlighted) first, then in their order. */
class GlobalPartnersSection extends Section
{
    public static function key(): string
    {
        return 'international.partners';
    }

    public static function label(): string
    {
        return __('international::international.section');
    }

    public static function view(): string
    {
        return 'international::sections.partners';
    }

    public static function model(): ?string
    {
        return Institution::class;
    }

    /** @return Collection<int, Institution> */
    public function items(SectionContext $ctx): Collection
    {
        $mode = $ctx->setting('source_mode', 'highlighted_first');
        if ($mode === 'manual') {
            return $ctx->applySourceMode(Institution::class);
        }
        $count = max(1, (int) $ctx->setting('count', 16));
        $highlighted = $mode === 'latest' ? (new Institution)->newCollection()
            : app(RelationEngine::class)->for($ctx->ownerKey)->content(Institution::class)->highlighted($count);
        if ($mode === 'highlighted') {
            return $highlighted;
        }
        $rest = InternationalWebsite::institutions($ctx->ownerKey)->whereKeyNot($highlighted->modelKeys())->limit(max(0, $count - $highlighted->count()))->get();

        return (new Institution)->newCollection($highlighted->concat($rest)->values()->all());
    }
}
