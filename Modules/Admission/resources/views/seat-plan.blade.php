{{-- /admission/seat-plan (FR-ADM-17). Contract: $page (AdmissionPage: seatIntro); the form is the admission.seat-lookup Livewire component --}}
<x-theme::layout :title="__('admission::admission.seats.page_title')">
    <div class="container" style="padding-block:var(--space-8, 32px);max-width:860px">
        <h1>{{ __('admission::admission.seats.page_title') }}</h1>
        @if ($intro = $page->seatIntro())
            <div class="prose">{!! $intro !!}</div>
        @else
            <p>{{ __('admission::admission.seats.lead') }}</p>
        @endif
        <livewire:admission.seat-lookup />
    </div>
</x-theme::layout>
