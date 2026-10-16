<?php

namespace Modules\Events\Sections;

use Filament\Forms\Components\TextInput;
use Illuminate\Support\Collection;
use Modules\Events\Models\Event;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Relations\RelationEngine;

/**
 * Homepage section "Past Event Highlights" (FR-EVT-08): past events highlighted in this place, in
 * highlight order; without any highlight, the latest past events.
 */
class PastEventsSection extends Section
{
    public static function key(): string
    {
        return 'events.past';
    }

    public static function label(): string
    {
        return __('events::events.section.past');
    }

    public static function view(): string
    {
        return 'events::sections.past';
    }

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('count')->label(__('events::events.section.count'))->integer()->minValue(1)->maxValue(12)->default(3),
        ];
    }

    /** @return Collection<int, Event> */
    public function items(SectionContext $ctx): Collection
    {
        $limit = max(1, (int) ($ctx->setting('count') ?: 3));
        $context = app(RelationEngine::class)->for($ctx->ownerKey)->content(Event::class);
        $past = $context->highlighted(50)->filter(fn (Event $e): bool => $e->state() === 'past')->take($limit)->values();

        return $past->isNotEmpty() ? $past : $context->query()->reorder()->past()->limit($limit)->get();
    }
}
