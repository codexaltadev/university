<?php

namespace Modules\Notice\Sections;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Collection;
use Modules\Notice\Models\Notice;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Relations\RelationEngine;

/**
 * Homepage section "Notice Ticker" (FR-NOT-06): a moving line of the latest notice titles on the
 * board — urgent ones first; optionally only urgent and pinned ones.
 */
class NoticeTickerSection extends Section
{
    public static function key(): string
    {
        return 'notice.ticker';
    }

    public static function label(): string
    {
        return __('notice::notice.section.ticker');
    }

    public static function view(): string
    {
        return 'notice::sections.ticker';
    }

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('count')->label(__('notice::notice.section.count'))->integer()->minValue(1)->maxValue(20)->default(6),
            Toggle::make('important_only')->label(__('notice::notice.section.important_only')),
        ];
    }

    /** @return Collection<int, Notice> */
    public function items(SectionContext $ctx): Collection
    {
        return app(RelationEngine::class)->for($ctx->ownerKey)->content(Notice::class)->query()->reorder()->onBoard()
            ->when((bool) $ctx->setting('important_only'), fn ($q) => $q->where(fn ($w) => $w->where('is_urgent', true)->orWhere('is_pinned', true)))
            ->orderByDesc('is_urgent')->boardOrder()->limit(max(1, (int) ($ctx->setting('count') ?: 6)))->get();
    }
}
