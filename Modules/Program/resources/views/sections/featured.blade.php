{{-- Homepage section "Featured Programs" (FR-PROG-16). Contract: $items (Collection<Program>), $section, $settings --}}
@if ($items->isNotEmpty())
    <div style="display:grid;gap:var(--space-6, 24px);grid-template-columns:repeat(auto-fill, minmax(260px, 1fr))">
        @foreach ($items as $program)
            @include('program::partials.card', ['program' => $program, 'style' => 'card'])
        @endforeach
    </div>
    <p style="margin-top:var(--space-6, 24px)"><a href="{{ url('/programs') }}">{{ __('program::program.view_all') }}</a></p>
@endif
