{{-- /programs, layout "list" (FR-PROG-12). Contract: $programs, $choices, $selected, $options --}}
<x-theme::layout :title="__('program::program.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('program::program.plural') }}</h1>
        @include('program::partials.filters')
        <div style="display:grid;gap:var(--space-4, 16px)">
            @forelse ($programs as $program)
                <div style="border-bottom:1px solid var(--color-border);padding-bottom:var(--space-4, 16px)">
                    @include('program::partials.card', ['program' => $program, 'style' => 'list'])
                </div>
            @empty
                <p class="program-empty">{{ __('program::program.no_results') }}</p>
            @endforelse
        </div>
    </div>
</x-theme::layout>
