{{-- /waiver-calculator (FR-FEE-06): page hero, then the waiver.calculator Livewire component as it is (its calculation and wire: contract untouched) in a card --}}
<x-theme::layout :title="__('waiver::waiver.calculator.title')">
    <x-theme::page-hero :title="__('waiver::waiver.calculator.title')" :text="__('waiver::waiver.calculator.intro')" :crumbs="[['label' => __('waiver::waiver.calculator.title')]]" />
    <div class="container page-body">
        <div class="card p-6 md:p-10"><livewire:waiver.calculator /></div>
        <p class="no-print mt-8"><a href="{{ url('/tuition-fees') }}" class="btn">{{ __('waiver::waiver.all_tuition_fees') }}<x-theme::icon name="arrow-right" width="16" height="16" /></a></p>
    </div>
</x-theme::layout>
