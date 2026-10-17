<?php

namespace Modules\Lab\Sections;

use Filament\Forms\Components\TextInput;
use Illuminate\Support\Collection;
use Modules\Lab\Models\Lab;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Statistics\StatContext;

/**
 * Homepage section "Labs" (FR-LAB-08, Phase 4.4b): the listed labs of this place — a department's
 * own, every lab under a faculty, all of them on the main site — in their order.
 */
class LabsSection extends Section
{
    public static function key(): string
    {
        return 'lab.list';
    }

    public static function label(): string
    {
        return __('lab::lab.section');
    }

    public static function view(): string
    {
        return 'lab::sections.list';
    }

    public static function settingsSchema(): array
    {
        return [TextInput::make('count')->label(__('lab::lab.section_count'))->integer()->minValue(1)->maxValue(24)->default(8)];
    }

    /** @return Collection<int, Lab> */
    public function items(SectionContext $ctx): Collection
    {
        $within = StatContext::of($ctx->ownerKey)->idsOf((new Lab)->getMorphClass());

        return Lab::query()->listed()->when($within !== null, fn ($q) => $q->whereKey($within))
            ->orderBy('sort_order')->limit(max(1, (int) ($ctx->setting('count') ?: 8)))->get();
    }
}
