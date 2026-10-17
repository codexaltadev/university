<?php

namespace Modules\Partners\Sections;

use Filament\Forms\Components\CheckboxList;
use Illuminate\Support\Collection;
use Modules\Partners\Models\Partner;
use Modules\Partners\Support\PartnersWebsite;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Relations\RelationEngine;

/** Homepage section "Industry Partners" (FR-PRT-05): a logo carousel; highlighted first (unless "latest"), then in their order. */
class PartnersSection extends Section
{
    public static function key(): string
    {
        return 'partners.list';
    }

    public static function label(): string
    {
        return __('partners::partners.section');
    }

    public static function view(): string
    {
        return 'partners::sections.list';
    }

    public static function model(): ?string
    {
        return Partner::class;
    }

    public static function settingsSchema(): array
    {
        return [CheckboxList::make('types')->label(__('partners::partners.fields.partner_type'))->columns(3)
            ->options(collect(Partner::TYPES)->mapWithKeys(fn (string $t): array => [$t => __('partners::partners.types.'.$t)])->all())
            ->helperText(__('partners::partners.help.section_types'))];
    }

    /** @return Collection<int, Partner> */
    public function items(SectionContext $ctx): Collection
    {
        $mode = $ctx->setting('source_mode', 'highlighted_first');
        if ($mode === 'manual') {
            return $ctx->applySourceMode(Partner::class);
        }
        $types = array_values(array_intersect((array) $ctx->setting('types', []), Partner::TYPES)) ?: array_values(array_diff(Partner::TYPES, ['discount']));
        $count = max(1, (int) $ctx->setting('count', 12));
        $highlighted = $mode === 'latest' ? (new Partner)->newCollection()
            : app(RelationEngine::class)->for($ctx->ownerKey)->content(Partner::class)->highlighted($count)->filter(fn (Partner $p): bool => in_array($p->partner_type, $types, true))->values();
        if ($mode === 'highlighted') {
            return $highlighted;
        }
        $rest = PartnersWebsite::shown($ctx->ownerKey)->ofType($types)->whereKeyNot($highlighted->modelKeys())->limit(max(0, $count - $highlighted->count()))->get();

        return (new Partner)->newCollection($highlighted->concat($rest)->values()->all());
    }
}
