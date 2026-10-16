<?php

namespace Modules\Notice\Sections;

use Filament\Forms\Components\TextInput;
use Illuminate\Support\Collection;
use Modules\Notice\Models\Notice;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Relations\RelationEngine;

/**
 * Homepage section "Notice Board" (FR-NOT-06): the place's latest notices still on the board,
 * pinned first (AC-NOT-02); expired ones drop out (FR-NOT-03).
 */
class NoticeBoardSection extends Section
{
    public static function key(): string
    {
        return 'notice.board';
    }

    public static function label(): string
    {
        return __('notice::notice.section.board');
    }

    public static function view(): string
    {
        return 'notice::sections.board';
    }

    public static function settingsSchema(): array
    {
        return [TextInput::make('count')->label(__('notice::notice.section.count'))->integer()->minValue(1)->maxValue(30)->default(8)];
    }

    /** @return Collection<int, Notice> */
    public function items(SectionContext $ctx): Collection
    {
        return app(RelationEngine::class)->for($ctx->ownerKey)->content(Notice::class)->query()->reorder()
            ->onBoard()->boardOrder()->limit(max(1, (int) ($ctx->setting('count') ?: 8)))->get();
    }
}
