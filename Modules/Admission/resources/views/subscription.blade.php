{{-- Subscription confirmed / unsubscribed (FR-ADM-09). Contract: $message --}}
<x-theme::layout :title="__('admission::admission.subscribe.title')">
    <div class="container" style="padding-block:var(--space-10, 40px);max-width:720px">
        <h1>{{ __('admission::admission.subscribe.title') }}</h1>
        <p>{{ $message }}</p>
        <p><a href="{{ url('/admission') }}">{{ __('admission::admission.title') }}</a></p>
    </div>
</x-theme::layout>
