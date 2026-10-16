{{-- "Upcoming Academic Dates" list (FR-CAL-07). Contract: $events (CalendarEvent list with calendar, type) --}}
<ul class="calendar-upcoming" style="list-style:none;padding:0;display:grid;gap:var(--space-3, 12px)">
    @foreach ($events as $event)
        <li style="display:flex;gap:var(--space-4, 16px);align-items:center">
            <span aria-hidden="true" style="flex:none;width:56px;text-align:center;border-radius:var(--radius-sm, 4px);color:#fff;background:{{ $event->color() }};padding:var(--space-1, 4px)">
                <strong style="display:block;font-size:1.4em;line-height:1">{{ $event->starts_on->day }}</strong>
                <small>{{ $event->starts_on->translatedFormat('M') }}</small>
            </span>
            <span>
                <strong>{{ $event->title }}</strong><br>
                <small>{{ $event->when() }} · <a href="{{ url($event->calendar->url()) }}">{{ $event->calendar->title }}</a></small>
            </span>
        </li>
    @endforeach
</ul>
