{{-- A short answer to a newsletter link. Contract: $message --}}
<x-theme::layout :title="__('newsletter::newsletter.title')">
    <div class="container" style="padding-block:var(--space-10, 40px);max-width:720px">
        <h1>{{ __('newsletter::newsletter.title') }}</h1>
        <p role="status">{{ $message }}</p>
        <p><a href="{{ url('/') }}">← {{ __('newsletter::newsletter.home') }}</a></p>
    </div>
</x-theme::layout>
