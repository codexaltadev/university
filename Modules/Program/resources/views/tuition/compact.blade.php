{{-- /tuition-fees, layout "compact" (FR-FEE-03): one table, faculty as a column. Contract: as "grouped" --}}
<x-theme::layout :title="__('program::tuition.tuition_fees')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('program::tuition.tuition_fees') }}@if ($category) — {{ $category->name }}@endif</h1>
        @include('program::tuition.filters')
        @if ($groups->isEmpty())
            <p class="tuition-empty">{{ __('program::tuition.no_results') }}</p>
        @else
            @include('program::tuition.table', ['tuitions' => $groups->flatMap(fn ($g) => $g['tuitions']), 'withFaculty' => true])
        @endif
    </div>
</x-theme::layout>
