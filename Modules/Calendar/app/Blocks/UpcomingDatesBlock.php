<?php

namespace Modules\Calendar\Blocks;

use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Modules\Calendar\Models\CalendarEvent;
use Modules\Calendar\Support\CalendarFinder;
use Platform\Pages\PageBlock;
use Platform\Taxonomy\Vocabularies;

/** "Upcoming Academic Dates" on any page (Module Guide "Content module checklist"): the same list as the homepage section. */
final class UpcomingDatesBlock extends PageBlock
{
    public static function name(): string
    {
        return 'calendar-upcoming';
    }

    public static function label(): string
    {
        return __('calendar::calendar.upcoming');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedCalendarDays;
    }

    public static function view(): string
    {
        return 'calendar::blocks.upcoming';
    }

    public static function schema(): array
    {
        return [
            TextInput::make('count')->label(__('calendar::calendar.block.count'))->integer()->minValue(1)->maxValue(20)->default(5),
            Select::make('audience')->label(__('calendar::calendar.fields.audience'))
                ->options(fn (): array => app(Vocabularies::class)->options(CalendarEvent::AUDIENCE))
                ->placeholder(__('calendar::calendar.everyone')),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, CalendarEvent>
     */
    public static function events(array $data): Collection
    {
        return app(CalendarFinder::class)->upcoming((int) ($data['count'] ?? 0) ?: 5, [], array_filter([(int) ($data['audience'] ?? 0)]));
    }
}
