{{-- /programs, layout "detailed" (FR-PROG-12): picture, facts and summary. Contract: $programs, $choices, $selected, $options --}}
<x-theme::layout :title="__('program::program.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('program::program.plural') }}</h1>
        @include('program::partials.filters')
        <div style="display:grid;gap:var(--space-8, 32px)">
            @forelse ($programs as $program)
                @include('program::partials.card', ['program' => $program, 'style' => 'detailed'])
            @empty
                <p class="program-empty">{{ __('program::program.no_results') }}</p>
            @endforelse
        </div>
    </div>
</x-theme::layout>
