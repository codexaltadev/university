{{-- Homepage section "Campus Facilities" (FR-FCL-04). Contract: $items (Facility list), $section, $settings (display); $context from the page --}}
@if ($items->isNotEmpty())
    @php($base = isset($context) && $context->isMicrosite() ? rtrim((string) app(\Platform\Context\MicrositeResolver::class)->urlFor($context->entity), '/') : '')
    @php($layout = ($settings['display'] ?? 'icons') === 'cards' ? 'cards' : 'icons')
    <div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax({{ $layout === 'icons' ? '160px' : '240px' }},1fr))">
        @foreach ($items as $facility)
            @include('facilities::partials.card', ['facility' => $facility, 'base' => $base, 'layout' => $layout])
        @endforeach
    </div>
    <p style="margin-top:var(--space-4, 16px)"><a class="button" href="{{ url($base.'/'.\Modules\Facilities\Models\Facility::PATH) }}">{{ __('facilities::facilities.all_facilities') }}</a></p>
@endif
