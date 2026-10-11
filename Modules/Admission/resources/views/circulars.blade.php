{{-- /admission/circulars (FR-ADM-16). Contract: $circulars (open first) --}}
<x-theme::layout :title="__('admission::admission.circular.plural')">
    <div class="container" style="padding-block:var(--space-8, 32px)">
        <nav class="breadcrumb" aria-label="{{ __('platform::relations.breadcrumb') }}">
            <ol>
                <li><a href="{{ url('/') }}">{{ __('platform::relations.home') }}</a><span aria-hidden="true"> › </span></li>
                <li><a href="{{ url('/admission') }}">{{ __('admission::admission.title') }}</a><span aria-hidden="true"> › </span></li>
                <li><span aria-current="page">{{ __('admission::admission.circular.plural') }}</span></li>
            </ol>
        </nav>
        <h1>{{ __('admission::admission.circular.plural') }}</h1>
        <div style="display:grid;gap:var(--space-6, 24px)">
            @forelse ($circulars as $circular)
                @include('admission::partials.circular-card', ['circular' => $circular])
            @empty
                <p>{{ __('admission::admission.no_circulars') }}</p>
            @endforelse
        </div>
    </div>
</x-theme::layout>
