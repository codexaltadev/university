{{-- /faculties, layout "list" (FR-FAC-07). Contract: $faculties, $options --}}
<x-theme::layout :title="__('faculty::faculty.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('faculty::faculty.plural') }}</h1>
        <div style="display:grid;gap:var(--space-4, 16px)">
            @foreach ($faculties as $faculty)
                <div style="border-bottom:1px solid var(--color-border);padding-bottom:var(--space-4, 16px)">
                    @include('faculty::partials.card', ['faculty' => $faculty, 'style' => 'list'])
                </div>
            @endforeach
        </div>
    </div>
</x-theme::layout>
