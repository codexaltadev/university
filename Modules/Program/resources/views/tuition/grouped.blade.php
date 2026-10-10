{{-- /tuition-fees, layout "grouped" (FR-FEE-03): a table per faculty. Contract: $category, $categories, $groups (list of [faculty, tuitions]), $choices, $selected, $options --}}
<x-theme::layout :title="__('program::tuition.tuition_fees')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('program::tuition.tuition_fees') }}@if ($category) — {{ $category->name }}@endif</h1>
        @include('program::tuition.filters')
        @forelse ($groups as $group)
            <section class="tuition-group" style="margin-top:var(--space-8, 32px)">
                <h2>{{ $group['faculty']->name }}</h2>
                @include('program::tuition.table', ['tuitions' => $group['tuitions']])
            </section>
        @empty
            <p class="tuition-empty">{{ __('program::tuition.no_results') }}</p>
        @endforelse
    </div>
</x-theme::layout>
