{{-- Homepage section "Admission going on" (FR-ADM-11). Contract: $items (running circulars), $section, $settings --}}
@if ($items->isNotEmpty())
    <div class="admission-going-on" style="display:grid;gap:var(--space-6, 24px)">
        @foreach ($items as $circular)
            @include('admission::partials.circular-card', ['circular' => $circular])
        @endforeach
        <p style="display:flex;flex-wrap:wrap;gap:var(--space-3, 12px)">
            @if ($apply = settings('admission.apply_url'))
                <a href="{{ $apply }}" class="button button-primary" target="_blank" rel="noopener">{{ __('admission::admission.apply_now') }}</a>
            @endif
            <a href="{{ url('/admission') }}" class="button">{{ __('admission::admission.title') }}</a>
        </p>
    </div>
@endif
