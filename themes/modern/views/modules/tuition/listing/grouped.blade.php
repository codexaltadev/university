{{-- /tuition-fees, layout "grouped" (FR-FEE-03): page hero with the module's filters, a table card per faculty. Contract: $category, $categories, $groups, $choices, $selected, $options --}}
<x-theme::layout :title="__('tuition::tuition.tuition_fees')">
    <x-theme::page-hero :title="__('tuition::tuition.tuition_fees').($category ? ' — '.$category->name : '')" :crumbs="[['label' => __('tuition::tuition.tuition_fees')]]">
        <div class="mt-6">@include('tuition::partials.filters')</div>
    </x-theme::page-hero>
    <div class="container page-body grid gap-8">
        @forelse ($groups as $group)
            <section class="card tuition-group overflow-x-auto">
                <h2 class="mb-4 text-2xl">{{ $group['faculty']->name }}</h2>
                @include('tuition::listing.table', ['tuitions' => $group['tuitions']])
            </section>
        @empty
            <p class="empty-state">{{ __('tuition::tuition.no_results') }}</p>
        @endforelse
    </div>
</x-theme::layout>
