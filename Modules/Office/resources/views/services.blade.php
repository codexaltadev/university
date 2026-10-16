{{-- /services (FR-OFF-06): every office's services. Contract: $groups (list of [office, services]) --}}
<x-theme::layout :title="__('office::office.services.plural')">
    <div class="container" style="padding-block:var(--space-8, 32px);max-width:960px" x-data="{ q: '' }">
        <h1>{{ __('office::office.services.plural') }}</h1>
        <input type="search" x-model="q" placeholder="{{ __('office::office.search_services') }}" aria-label="{{ __('office::office.search_services') }}"
               style="width:100%;padding:var(--space-3, 12px);border:1px solid var(--color-border);border-radius:var(--radius-sm, 4px);margin-block:var(--space-4, 16px)">
        @forelse ($groups as $group)
            <section style="margin-top:var(--space-6, 24px)">
                <h2>@if ($url = $group['office']->url())<a href="{{ $url }}">{{ $group['office']->name }}</a>@else{{ $group['office']->name }}@endif</h2>
                <div style="display:grid;gap:var(--space-2, 8px)">
                    @foreach ($group['services'] as $service)
                        <div x-show="q.trim() === '' || $el.dataset.search.includes(q.trim().toLowerCase())" data-search="{{ mb_strtolower(strip_tags($service->title.' '.$service->description.' '.$group['office']->name)) }}">
                            @include('office::partials.service', ['service' => $service])
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <p>{{ __('office::office.no_services') }}</p>
        @endforelse
    </div>
</x-theme::layout>
