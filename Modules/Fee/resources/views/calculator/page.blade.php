{{-- /waiver-calculator (FR-FEE-06). Contract: none — the form is the fee.waiver-calculator Livewire component --}}
<x-theme::layout :title="__('fee::fee.calculator.title')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('fee::fee.calculator.title') }}</h1>
        <p class="no-print">{{ __('fee::fee.calculator.intro') }}</p>
        <livewire:fee.waiver-calculator />
        <p class="no-print" style="margin-top:var(--space-8, 32px)"><a href="{{ url('/tuition-fees') }}">{{ __('fee::fee.all_tuition_fees') }}</a></p>
    </div>
</x-theme::layout>
