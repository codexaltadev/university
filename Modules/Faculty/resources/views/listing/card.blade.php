{{-- /faculties, layout "card" (FR-FAC-07): picture, name and summary on a raised card. Contract: $faculties, $options --}}
<x-theme::layout :title="__('faculty::faculty.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('faculty::faculty.plural') }}</h1>
        <div style="display:grid;gap:var(--space-6, 24px);grid-template-columns:repeat(auto-fill, minmax(min(100%, calc(100% / {{ (int) ($options['per_row'] ?? 2) }} - 24px)), 1fr))">
            @foreach ($faculties as $faculty)
                <div style="background:var(--color-bg);box-shadow:var(--shadow-card);border-radius:var(--radius-lg, 16px);padding:var(--space-4, 16px)">
                    @include('faculty::partials.card', ['faculty' => $faculty, 'style' => 'card'])
                </div>
            @endforeach
        </div>
    </div>
</x-theme::layout>
