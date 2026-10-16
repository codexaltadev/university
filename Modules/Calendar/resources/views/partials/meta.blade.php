{{-- A calendar's session and duration line. Contract: $calendar --}}
<p class="calendar-meta" style="color:var(--color-muted, #6b7280)">
    {{ collect([$calendar->session_label, $calendar->duration_label])->filter()->implode(' · ') }}
    @if ($calendar->isCurrent())
        <span class="badge badge-success">{{ __('calendar::calendar.running') }}</span>
    @endif
</p>
