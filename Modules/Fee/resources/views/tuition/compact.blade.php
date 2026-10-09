{{-- /tuition-fees, layout "compact" (FR-FEE-03): one table, faculty as a column. Contract: as "grouped" --}}
<x-theme::layout :title="__('fee::fee.tuition_fees')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('fee::fee.tuition_fees') }}@if ($category) — {{ $category->name }}@endif</h1>
        @include('fee::partials.filters')
        @if ($groups->isEmpty())
            <p class="tuition-empty">{{ __('fee::fee.no_results') }}</p>
        @else
            @include('fee::tuition.table', ['tuitions' => $groups->flatMap(fn ($g) => $g['tuitions']), 'withFaculty' => true])
        @endif
    </div>
</x-theme::layout>
