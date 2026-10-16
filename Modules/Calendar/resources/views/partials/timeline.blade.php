{{-- Timeline view (FR-CAL-04). Contract: $events (CalendarEvent list, in order) --}}
<ol class="calendar-timeline" style="list-style:none;padding:0;margin:0;border-left:3px solid var(--color-primary, #034EA2)">
    @forelse ($events as $event)
        <li style="position:relative;padding:0 0 var(--space-5, 20px) var(--space-6, 24px)">
            <span aria-hidden="true" style="position:absolute;left:-9px;top:4px;width:15px;height:15px;border-radius:50%;background:{{ $event->color() }}"></span>
            <div style="font-weight:600">{{ $event->when() }}</div>
            <div>{{ $event->title }} @include('calendar::partials.type', ['event' => $event])</div>
        </li>
    @empty
        <li style="padding-left:var(--space-6, 24px)">{{ __('calendar::calendar.no_dates') }}</li>
    @endforelse
</ol>
