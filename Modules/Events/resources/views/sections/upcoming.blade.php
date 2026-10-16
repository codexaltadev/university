{{-- Homepage section "Upcoming Events" (FR-EVT-08). Contract: $items (Event list), $section, $settings (count, countdown); $context from the page --}}
@if ($items->isNotEmpty())
    @php($base = isset($context) && $context->isMicrosite() ? rtrim((string) app(\Platform\Context\MicrositeResolver::class)->urlFor($context->entity), '/') : '')
    @if (($settings['countdown'] ?? false) && ($first = $items->first())->state() === 'upcoming')
        <p class="events-countdown" x-data="{ left: {{ max(0, $first->starts_at->timestamp - now()->timestamp) }}, tick() { this.left = Math.max(0, this.left - 1) } }" x-init="setInterval(() => tick(), 1000)" style="font-weight:600">
            {{ $first->title }}:
            <span x-text="Math.floor(left / 86400) + 'd ' + Math.floor(left % 86400 / 3600) + 'h ' + Math.floor(left % 3600 / 60) + 'm ' + (left % 60) + 's'">{{ $first->starts_at->diffForHumans() }}</span>
        </p>
    @endif
    <div style="display:grid;gap:var(--space-3, 12px)">
        @foreach ($items as $event)
            @include('events::partials.card', ['event' => $event, 'layout' => 'list', 'base' => $base])
        @endforeach
    </div>
    <p style="margin-top:var(--space-4, 16px)"><a href="{{ url($base.'/'.\Modules\Events\Models\Event::PATH) }}" class="button">{{ __('events::events.all_events') }}</a></p>
@endif
