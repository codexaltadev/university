<?php

namespace Modules\Events\Sections;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Collection;
use Modules\Events\Models\Event;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Relations\RelationEngine;

/**
 * Homepage section "Upcoming Events" (FR-EVT-08): the place's events that are not over, by
 * start; an event drops out by itself once it is over (AC-EVT-01). Optional countdown to the first.
 */
class UpcomingEventsSection extends Section
{
    public static function key(): string
    {
        return 'events.upcoming';
    }

    public static function label(): string
    {
        return __('events::events.section.upcoming');
    }

    public static function view(): string
    {
        return 'events::sections.upcoming';
    }

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('count')->label(__('events::events.section.count'))->integer()->minValue(1)->maxValue(12)->default(4),
            Toggle::make('countdown')->label(__('events::events.section.countdown')),
        ];
    }

    /** @return Collection<int, Event> */
    public function items(SectionContext $ctx): Collection
    {
        return app(RelationEngine::class)->for($ctx->ownerKey)->content(Event::class)->query()->reorder()
            ->upcoming()->limit(max(1, (int) ($ctx->setting('count') ?: 4)))->get();
    }
}
