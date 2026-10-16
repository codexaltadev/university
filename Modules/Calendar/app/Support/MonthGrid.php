<?php

namespace Modules\Calendar\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Calendar\Models\CalendarEvent;

/**
 * Month view (FR-CAL-04): every month the dated dates touch, as weeks of seven days
 * (Saturday first, as in Bangladesh), each day with the dates on it.
 */
class MonthGrid
{
    /**
     * @param  Collection<int, CalendarEvent>  $events
     * @return list<array{month: Carbon, weeks: list<list<array{day: Carbon, inMonth: bool, events: list<CalendarEvent>}>>}>
     */
    public static function build(Collection $events): array
    {
        $dated = $events->filter(fn (CalendarEvent $e): bool => $e->starts_on !== null)->values();
        if ($dated->isEmpty()) {
            return [];
        }

        $first = $dated->min('starts_on')->copy()->startOfMonth();
        $last = $dated->map(fn (CalendarEvent $e): Carbon => $e->lastDay())->max()->copy()->startOfMonth();
        $months = [];

        for ($month = $first->copy(); $month->lte($last) && count($months) < 24; $month->addMonth()) {
            $start = $month->copy()->startOfWeek(Carbon::SATURDAY);
            $end = $month->copy()->endOfMonth()->endOfWeek(Carbon::FRIDAY);
            $weeks = [];
            for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
                $on = $day->copy();
                $weeks[intdiv((int) $start->diffInDays($on), 7)][] = [
                    'day' => $on,
                    'inMonth' => $on->isSameMonth($month),
                    'events' => $dated->filter(fn (CalendarEvent $e): bool => $on->betweenIncluded($e->starts_on, $e->lastDay()))->values()->all(),
                ];
            }
            $months[] = ['month' => $month->copy(), 'weeks' => array_values($weeks)];
        }

        return $months;
    }
}
