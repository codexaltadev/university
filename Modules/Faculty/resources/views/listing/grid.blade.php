{{-- /faculties, layout "grid" (FR-FAC-07). Contract: $faculties (Collection<Faculty>), $options (per_row, sidebar) --}}
<x-theme::layout :title="__('faculty::faculty.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('faculty::faculty.plural') }}</h1>
        <div style="display:grid;gap:var(--space-6, 24px);grid-template-columns:repeat(auto-fill, minmax(min(100%, calc(100% / {{ (int) ($options['per_row'] ?? 3) }} - 24px)), 1fr))">
            @foreach ($faculties as $faculty)
                @include('faculty::partials.card', ['faculty' => $faculty, 'style' => 'grid'])
            @endforeach
        </div>
    </div>
</x-theme::layout>
