{{-- "Upcoming Academic Dates" page block. Contract: $data (count, audience), $locale --}}
@php($events = \Modules\Calendar\Blocks\UpcomingDatesBlock::events((array) $data))
@if ($events->isNotEmpty())
    <div class="block block--calendar-upcoming">
        @include('calendar::partials.upcoming', ['events' => $events])
    </div>
@endif
