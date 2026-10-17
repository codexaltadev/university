{{-- Homepage section "Past Event Highlights" (FR-EVT-08). Contract: $items (Event list), $section, $settings (count); $context from the page --}}
@if ($items->isNotEmpty())
    @php($base = isset($context) && $context->isMicrosite() ? rtrim((string) app(\Platform\Context\MicrositeResolver::class)->urlFor($context->entity), '/') : '')
    <div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(280px,1fr))">
        @foreach ($items as $event)
            @include('events::partials.card', ['event' => $event, 'layout' => 'cards', 'base' => $base])
        @endforeach
    </div>
    <p style="margin-top:var(--space-4, 16px)"><a href="{{ url($base.'/'.\Modules\Events\Models\Event::PATH).'?tab=past' }}" class="btn">{{ __('events::events.tabs.past') }}</a></p>
@endif
