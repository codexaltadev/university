<?php

namespace Modules\Calendar\Tabs;

use Illuminate\Database\Eloquent\Model;
use Modules\Calendar\Models\Calendar;
use Modules\Calendar\Support\CalendarFinder;
use Platform\Website\EntityTab;

/**
 * "Academic Calendar" on a program's page (FR-CAL-09 through FR-PROG-18): the current calendar
 * of the program's faculty, only the dates for the program's level (and for everyone), with a
 * link to the full calendar. No tab without one.
 */
class ProgramCalendarTab extends EntityTab
{
    public static function key(): string
    {
        return 'calendar.program';
    }

    public static function label(): string
    {
        return __('calendar::calendar.plural');
    }

    public static function view(): string
    {
        return 'calendar::tabs.program';
    }

    public static function sort(): int
    {
        return 60;
    }

    /** @var array<int, array{calendar: ?Calendar, events: mixed, audience: list<int>}> */
    protected array $loaded = [];

    public function visible(Model $entity): bool
    {
        return $this->load($entity)['calendar'] !== null;
    }

    public function data(Model $entity): array
    {
        return $this->load($entity);
    }

    protected function load(Model $program): array
    {
        return $this->loaded[$program->getKey()] ??= (function () use ($program): array {
            $finder = app(CalendarFinder::class);
            $faculties = $finder->facultiesOf($program);
            $calendar = $faculties === [] ? null : Calendar::current($finder->calendars($faculties)->with('events')->get())->first();
            $audience = $finder->audienceOfProgram($program);

            return [
                'calendar' => $calendar,
                'events' => $calendar?->events()->frontendVisible()->with('type')->when($audience !== [], fn ($q) => $q->forAudience($audience))->get() ?? collect(),
                'audience' => $audience,
            ];
        })();
    }
}
