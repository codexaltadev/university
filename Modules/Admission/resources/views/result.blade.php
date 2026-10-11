{{-- /admission/result (FR-ADM-06). Contract: $intro (settings text or null); the form is the admission.result-lookup Livewire component --}}
<x-theme::layout :title="__('admission::admission.result_page.title')">
    <div class="container" style="padding-block:var(--space-8, 32px);max-width:860px">
        <nav class="breadcrumb" aria-label="{{ __('platform::relations.breadcrumb') }}">
            <ol>
                <li><a href="{{ url('/') }}">{{ __('platform::relations.home') }}</a><span aria-hidden="true"> › </span></li>
                <li><a href="{{ url('/admission') }}">{{ __('admission::admission.title') }}</a><span aria-hidden="true"> › </span></li>
                <li><span aria-current="page">{{ __('admission::admission.result_page.title') }}</span></li>
            </ol>
        </nav>
        <h1>{{ __('admission::admission.result_page.title') }}</h1>
        @if ($intro)
            <div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml($intro) !!}</div>
        @endif
        <livewire:admission.result-lookup />
    </div>
</x-theme::layout>
