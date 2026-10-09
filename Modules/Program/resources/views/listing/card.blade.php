{{-- /programs, layout "card" (FR-PROG-12). Contract: $programs (Collection<Program>), $choices, $selected, $options (per_row, sidebar) --}}
<x-theme::layout :title="__('program::program.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('program::program.plural') }}</h1>
        @include('program::partials.filters')
        @forelse ($programs as $program)
            @if ($loop->first)<div style="display:grid;gap:var(--space-6, 24px);grid-template-columns:repeat(auto-fill, minmax(min(100%, calc(100% / {{ (int) ($options['per_row'] ?? 3) }} - 24px)), 1fr))">@endif
            <div style="background:var(--color-bg);box-shadow:var(--shadow-card);border-radius:var(--radius-lg, 16px);padding:var(--space-4, 16px)">
                @include('program::partials.card', ['program' => $program, 'style' => 'card'])
            </div>
            @if ($loop->last)</div>@endif
        @empty
            <p class="program-empty">{{ __('program::program.no_results') }}</p>
        @endforelse
    </div>
</x-theme::layout>
