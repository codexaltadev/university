<?php

namespace Modules\Csr\Sections;

use Illuminate\Support\Collection;
use Modules\Csr\Models\Activity;
use Modules\Csr\Support\CsrWebsite;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Relations\RelationEngine;

/** Homepage section "Social Responsibility" (FR-CSR-03/04): highlighted first (unless "latest"), then the newest. */
class CsrSection extends Section
{
    public static function key(): string
    {
        return 'csr.list';
    }

    public static function label(): string
    {
        return __('csr::csr.section');
    }

    public static function view(): string
    {
        return 'csr::sections.list';
    }

    public static function model(): ?string
    {
        return Activity::class;
    }

    /** @return Collection<int, Activity> */
    public function items(SectionContext $ctx): Collection
    {
        $mode = $ctx->setting('source_mode', 'highlighted_first');
        if ($mode === 'manual') {
            return $ctx->applySourceMode(Activity::class);
        }
        $count = max(1, (int) $ctx->setting('count', 6));
        $highlighted = $mode === 'latest' ? (new Activity)->newCollection()
            : app(RelationEngine::class)->for($ctx->ownerKey)->content(Activity::class)->highlighted($count);
        if ($mode === 'highlighted') {
            return $highlighted;
        }
        $rest = CsrWebsite::shown($ctx->ownerKey)->whereKeyNot($highlighted->modelKeys())->limit(max(0, $count - $highlighted->count()))->get();

        return (new Activity)->newCollection($highlighted->concat($rest)->values()->all());
    }
}
