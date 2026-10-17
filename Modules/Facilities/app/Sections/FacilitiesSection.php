<?php

namespace Modules\Facilities\Sections;

use Filament\Forms\Components\Radio;
use Illuminate\Support\Collection;
use Modules\Facilities\Models\Facility;
use Modules\Facilities\Support\FacilitiesWebsite;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Relations\RelationEngine;

/** Homepage section "Campus Facilities" (FR-FCL-04): highlighted first (unless "latest"), then in their order. */
class FacilitiesSection extends Section
{
    public static function key(): string
    {
        return 'facilities.list';
    }

    public static function label(): string
    {
        return __('facilities::facilities.section');
    }

    public static function view(): string
    {
        return 'facilities::sections.list';
    }

    public static function model(): ?string
    {
        return Facility::class;
    }

    public static function settingsSchema(): array
    {
        return [Radio::make('display')->label(__('facilities::facilities.display'))->inline()->default('icons')
            ->options(['icons' => __('facilities::facilities.layouts.icons'), 'cards' => __('facilities::facilities.layouts.cards')])];
    }

    /** @return Collection<int, Facility> */
    public function items(SectionContext $ctx): Collection
    {
        $mode = $ctx->setting('source_mode', 'highlighted_first');
        if ($mode === 'manual') {
            return $ctx->applySourceMode(Facility::class);
        }
        $count = max(1, (int) $ctx->setting('count', 8));
        $highlighted = $mode === 'latest' ? (new Facility)->newCollection()
            : app(RelationEngine::class)->for($ctx->ownerKey)->content(Facility::class)->highlighted($count);
        if ($mode === 'highlighted') {
            return $highlighted;
        }
        $rest = FacilitiesWebsite::shown($ctx->ownerKey)->whereKeyNot($highlighted->modelKeys())->limit(max(0, $count - $highlighted->count()))->get();

        return (new Facility)->newCollection($highlighted->concat($rest)->values()->all());
    }
}
