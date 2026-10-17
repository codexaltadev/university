{{-- Homepage section "Upcoming Academic Dates" (FR-CAL-07). Contract: $items (CalendarEvent list), $section, $settings --}}
@if ($items->isNotEmpty())
    <div class="calendar-upcoming-section">
        @include('calendar::partials.upcoming', ['events' => $items])
        <p style="margin-top:var(--space-4, 16px)"><a href="{{ url('/'.\Modules\Calendar\Models\Calendar::PATH) }}" class="btn">{{ __('calendar::calendar.full_calendar') }}</a></p>
    </div>
@endif
