{{-- /tuition-fees, layout "compact" (FR-FEE-03): one table, faculty as a column. Contract: as "grouped" --}}
<x-theme::layout :title="__('tuition::tuition.tuition_fees')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('tuition::tuition.tuition_fees') }}@if ($category) — {{ $category->name }}@endif</h1>
        @include('tuition::partials.filters')
        @if ($groups->isEmpty())
            <p class="tuition-empty">{{ __('tuition::tuition.no_results') }}</p>
        @else
            @include('tuition::listing.table', ['tuitions' => $groups->flatMap(fn ($g) => $g['tuitions']), 'withFaculty' => true])
        @endif
    </div>
</x-theme::layout>
