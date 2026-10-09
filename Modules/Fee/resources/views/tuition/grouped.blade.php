{{-- /tuition-fees, layout "grouped" (FR-FEE-03): a table per faculty. Contract: $category, $categories, $groups (list of [faculty, tuitions]), $choices, $selected, $options --}}
<x-theme::layout :title="__('fee::fee.tuition_fees')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('fee::fee.tuition_fees') }}@if ($category) — {{ $category->name }}@endif</h1>
        @include('fee::partials.filters')
        @forelse ($groups as $group)
            <section class="tuition-group" style="margin-top:var(--space-8, 32px)">
                <h2>{{ $group['faculty']->name }}</h2>
                @include('fee::tuition.table', ['tuitions' => $group['tuitions']])
            </section>
        @empty
            <p class="tuition-empty">{{ __('fee::fee.no_results') }}</p>
        @endforelse
    </div>
</x-theme::layout>
