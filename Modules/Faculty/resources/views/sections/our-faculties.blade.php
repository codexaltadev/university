{{-- Homepage section "Our Faculties" (FR-FAC-08). Contract: $items (Collection<Faculty>), $section, $settings --}}
@if ($items->isNotEmpty())
    <div style="display:grid;gap:var(--space-6, 24px);grid-template-columns:repeat(auto-fill, minmax(240px, 1fr))">
        @foreach ($items as $faculty)
            @include('faculty::partials.card', ['faculty' => $faculty, 'style' => 'grid'])
        @endforeach
    </div>
    <p style="margin-top:var(--space-6, 24px)"><a href="{{ url('/faculties') }}">{{ __('faculty::faculty.view_all') }}</a></p>
@endif
