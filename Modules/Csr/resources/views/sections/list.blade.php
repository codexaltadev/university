{{-- Homepage section "Social Responsibility" (FR-CSR-03). Contract: $items (Activity list), $section, $settings; $context from the page --}}
@if ($items->isNotEmpty())
    @php($base = isset($context) && $context->isMicrosite() ? rtrim((string) app(\Platform\Context\MicrositeResolver::class)->urlFor($context->entity), '/') : '')
    <div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
        @foreach ($items as $activity)
            @include('csr::partials.card', ['activity' => $activity, 'base' => $base, 'layout' => 'cards'])
        @endforeach
    </div>
    <p style="margin-top:var(--space-4, 16px)"><a class="btn" href="{{ url($base.'/'.\Modules\Csr\Models\Activity::PATH) }}">{{ __('csr::csr.all_activities') }}</a></p>
@endif
