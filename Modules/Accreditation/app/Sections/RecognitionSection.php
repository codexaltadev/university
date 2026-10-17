<?php

namespace Modules\Accreditation\Sections;

use Filament\Forms\Components\Radio;
use Illuminate\Support\Collection;
use Modules\Accreditation\Models\Accreditation;
use Modules\Accreditation\Models\Ranking;
use Modules\Accreditation\Support\AccreditationWebsite;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Relations\RelationEngine;

/**
 * Homepage section "Rankings & Recognition" (FR-ACR-06): each ranking's latest result, then the
 * accreditations (highlighted first) — as a logo strip or cards.
 */
class RecognitionSection extends Section
{
    public static function key(): string
    {
        return 'accreditation.recognition';
    }

    public static function label(): string
    {
        return __('accreditation::accreditation.section');
    }

    public static function view(): string
    {
        return 'accreditation::sections.recognition';
    }

    public static function model(): ?string
    {
        return Accreditation::class;
    }

    public static function settingsSchema(): array
    {
        return [
            Radio::make('show')->label(__('accreditation::accreditation.show'))->inline()->default('both')
                ->options(['both' => __('accreditation::accreditation.show_both'), 'rankings' => __('accreditation::accreditation.rankings'), 'accreditations' => __('accreditation::accreditation.plural')]),
            Radio::make('display')->label(__('accreditation::accreditation.display'))->inline()->default('strip')
                ->options(['strip' => __('accreditation::accreditation.layouts.strip'), 'cards' => __('accreditation::accreditation.layouts.cards')]),
        ];
    }

    /** @return Collection<int, Accreditation|Ranking> */
    public function items(SectionContext $ctx): Collection
    {
        $show = $ctx->setting('show', 'both');
        $count = max(1, (int) $ctx->setting('count', 8));
        $rankings = $show === 'accreditations' ? collect() : AccreditationWebsite::latest(AccreditationWebsite::rankings($ctx->ownerKey)->where('scope', 'overall')->get());
        if ($show === 'rankings') {
            return $rankings->take($count)->values();
        }
        $mode = $ctx->setting('source_mode', 'highlighted_first');
        if ($mode === 'manual') {
            $accreditations = $ctx->applySourceMode(Accreditation::class);
        } else {
            $highlighted = $mode === 'latest' ? collect()
                : app(RelationEngine::class)->for($ctx->ownerKey)->content(Accreditation::class)->highlighted($count)->reject(fn (Accreditation $a): bool => $a->isExpired() && settings('accreditation.expired', 'hide') === 'hide');
            $accreditations = $mode === 'highlighted' ? $highlighted
                : $highlighted->concat(AccreditationWebsite::accreditations($ctx->ownerKey)->whereKeyNot($highlighted->modelKeys())->limit($count)->get());
        }

        return collect($rankings)->concat($accreditations)->take($count)->values();
    }
}
