{{-- /programs, layout "card" (FR-PROG-12): page hero with the module's filters, then the program cards. Contract: $programs, $choices, $selected, $options --}}
<x-theme::layout :title="__('program::program.plural')">
    <x-theme::page-hero :title="__('program::program.plural')" :crumbs="[['label' => __('program::program.plural')]]">
        <div class="mt-6">@include('program::partials.filters')</div>
    </x-theme::page-hero>
    <div class="container page-body">
        @if ($programs->isEmpty())
            <p class="empty-state">{{ __('program::program.no_results') }}</p>
        @else
            <div class="grid-cards">
                @foreach ($programs as $program)
                    @include('program::partials.card', ['program' => $program, 'style' => 'card'])
                @endforeach
            </div>
        @endif
    </div>
</x-theme::layout>
