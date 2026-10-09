{{-- Homepage section "Departments" (FR-DEPT-09, FR-FAC-06). Contract: $items (Collection<Department>), $section, $settings --}}
@if ($items->isNotEmpty())
    <div style="display:grid;gap:var(--space-6, 24px);grid-template-columns:repeat(auto-fill, minmax(240px, 1fr))">
        @foreach ($items as $department)
            @include('department::partials.card', ['department' => $department, 'style' => 'grid'])
        @endforeach
    </div>
    <p style="margin-top:var(--space-6, 24px)"><a href="{{ url('/departments') }}">{{ __('department::department.view_all') }}</a></p>
@endif
