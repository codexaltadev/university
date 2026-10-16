{{-- Program page tab "Academic Calendar" (FR-CAL-09). Contract: $entity, $calendar, $events (for the program's level and everyone) --}}
<h3>{{ $calendar->title }}</h3>
@include('calendar::partials.meta', ['calendar' => $calendar])
@include('calendar::partials.table', ['events' => $events])
<p><a href="{{ url($calendar->url()) }}">{{ __('calendar::calendar.full_calendar') }}</a></p>
