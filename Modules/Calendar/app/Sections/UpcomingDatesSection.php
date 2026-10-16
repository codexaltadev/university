<?php

namespace Modules\Calendar\Sections;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Collection;
use Modules\Calendar\Models\CalendarEvent;
use Modules\Calendar\Support\CalendarFinder;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Taxonomy\Vocabularies;

/**
 * Homepage section "Upcoming Academic Dates" (FR-CAL-07): the next dates of the calendars
 * shown on the homepage — on a microsite only its faculty's — for one audience or all.
 * Nothing to come = not shown.
 */
class UpcomingDatesSection extends Section
{
    public static function key(): string
    {
        return 'calendar.upcoming';
    }

    public static function label(): string
    {
        return __('calendar::calendar.upcoming');
    }

    public static function view(): string
    {
        return 'calendar::sections.upcoming';
    }

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('count')->label(__('calendar::calendar.block.count'))->integer()->minValue(1)->maxValue(20)->default(5),
            Select::make('audience')->label(__('calendar::calendar.fields.audience'))
                ->options(fn (): array => app(Vocabularies::class)->options(CalendarEvent::AUDIENCE))
                ->placeholder(__('calendar::calendar.everyone')),
        ];
    }

    /** @return Collection<int, CalendarEvent> */
    public function items(SectionContext $ctx): Collection
    {
        $finder = app(CalendarFinder::class);
        $site = $ctx->site();
        $faculties = $site->isMicrosite() ? $finder->facultiesOf($site->entity) : [];
        if ($site->isMicrosite() && $faculties === []) {
            return collect();
        }

        return $finder->upcoming((int) ($ctx->setting('count') ?: 5), $faculties, array_filter([(int) $ctx->setting('audience')]));
    }
}
