{{-- /waiver-calculator (FR-FEE-06). Contract: none — the form is the waiver.calculator Livewire component --}}
<x-theme::layout :title="__('waiver::waiver.calculator.title')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('waiver::waiver.calculator.title') }}</h1>
        <p class="no-print">{{ __('waiver::waiver.calculator.intro') }}</p>
        <livewire:waiver.calculator />
        <p class="no-print" style="margin-top:var(--space-8, 32px)"><a href="{{ url('/tuition-fees') }}">{{ __('waiver::waiver.all_tuition_fees') }}</a></p>
    </div>
</x-theme::layout>
