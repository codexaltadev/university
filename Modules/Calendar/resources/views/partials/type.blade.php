{{-- A date's type as a coloured badge (FR-CAL-03). Contract: $event --}}
@if ($event->type)
    <span class="badge calendar-type" style="background:{{ $event->color('var(--color-muted, #6b7280)') }};color:#fff">{{ $event->type->name }}</span>
@endif
