<?php

namespace Modules\Calendar\Blocks;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Modules\Calendar\Models\Calendar;
use Modules\Calendar\Support\CalendarFinder;
use Modules\Calendar\Support\CalendarPage;
use Platform\Pages\PageBlock;

/**
 * "Academic Calendar" on any page, as DIU's /registrar-office/academic-calendar: the running calendar's
 * card — the session to switch to (in place, the page stays), year, duration, its dates — over the
 * latest few calendars.
 */
final class CalendarSessionsBlock extends PageBlock
{
    public const COUNT = 8;

    public static function name(): string
    {
        return 'calendar-sessions';
    }

    public static function label(): string
    {
        return __('calendar::calendar.plural');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedCalendar;
    }

    public static function view(): string
    {
        return 'calendar::blocks.sessions';
    }

    public static function schema(): array
    {
        return [
            TextInput::make('count')->label(__('calendar::calendar.block.sessions'))->integer()->minValue(1)->maxValue(30)->default(self::COUNT),
        ];
    }

    /** The calendars to switch between, the running one(s) first, each with its dates (CalendarPage cards) */
    public static function page(array $data): CalendarPage
    {
        $all = app(CalendarFinder::class)->calendars()->limit((int) ($data['count'] ?? 0) ?: self::COUNT)->get();
        $current = Calendar::current($all);

        return new CalendarPage(['current' => $current->concat($all->reject(fn (Calendar $c): bool => $current->contains($c)))
            ->map(fn (Calendar $c): array => ['calendar' => $c, 'events' => $c->events()->frontendVisible()->with(['type', 'terms'])->get()])->values()->all()]);
    }
}
