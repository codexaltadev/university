{{-- /accreditations (FR-ACR-05). Contract: $groups (['term' => ?Term, 'items' => list]), $base --}}
<x-theme::layout :title="__('accreditation::accreditation.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('accreditation::accreditation.plural') }}</h1>
        @if ($groups === [])<p style="margin-top:var(--space-8, 32px)">{{ __('accreditation::accreditation.none') }}</p>@endif
        @foreach ($groups as $group)
            <section style="margin-top:var(--space-8, 32px)">
                @if (count($groups) > 1)<h2>{{ $group['term']?->name ?? __('accreditation::accreditation.other') }}</h2>@endif
                <div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(320px,1fr))">
                    @foreach ($group['items'] as $accreditation)
                        @include('accreditation::partials.accreditation', ['accreditation' => $accreditation, 'base' => $base])
                    @endforeach
                </div>
            </section>
        @endforeach
        <p style="margin-top:var(--space-8, 32px)"><a class="btn btn--outline" href="{{ url($base.'/'.\Modules\Accreditation\Models\Ranking::PATH) }}">{{ __('accreditation::accreditation.rankings') }}</a></p>
    </div>
</x-theme::layout>
