{{-- /international/partners (FR-INTL-02). Contract: $partners (paginator, or null for "countries"), $countries, $perCountry (country_id → count), $country, $type, $q, $layout (grid / table / countries), $base --}}
@php($path = url($base.'/'.\Modules\International\Support\InternationalWebsite::ROOT.'/partners'))
<x-theme::layout :title="__('international::international.partners')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('international::international.partners') }}</h1>
        <form method="get" action="{{ $path }}" style="display:flex;flex-wrap:wrap;gap:var(--space-2, 8px);margin-top:var(--space-4, 16px);align-items:center">
            <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('international::international.search') }}" aria-label="{{ __('international::international.search') }}" style="flex:1 1 220px;padding:8px 12px;border:1px solid var(--color-border);border-radius:var(--radius, 8px)">
            <select name="country" aria-label="{{ __('international::international.fields.country') }}" style="padding:8px;border:1px solid var(--color-border);border-radius:var(--radius, 8px)">
                <option value="">{{ __('international::international.all_countries') }}</option>
                @foreach ($countries as $c)<option value="{{ $c->iso2 }}" @selected($country?->is($c))>{{ $c->name }}</option>@endforeach
            </select>
            <select name="type" aria-label="{{ __('international::international.fields.agreement_type') }}" style="padding:8px;border:1px solid var(--color-border);border-radius:var(--radius, 8px)">
                <option value="">{{ __('international::international.all_agreements') }}</option>
                @foreach (\Modules\International\Models\Institution::AGREEMENTS as $a)<option value="{{ $a }}" @selected($type === $a)>{{ __('international::international.agreements.'.$a) }}</option>@endforeach
            </select>
            <input type="hidden" name="view" value="{{ $layout }}">
            <button class="button" type="submit">{{ __('international::international.go') }}</button>
        </form>
        <nav style="display:flex;gap:var(--space-2, 8px);margin-top:var(--space-3, 12px)">
            @foreach (\Modules\International\Support\InternationalWebsite::VIEWS as $v)
                <a href="{{ $path.'?'.http_build_query(array_filter(['q' => $q, 'country' => $country?->iso2, 'type' => $type, 'view' => $v])) }}" @class(['button', 'button--outline' => $layout !== $v])>{{ __('international::international.layouts.'.$v) }}</a>
            @endforeach
        </nav>

        @if ($layout === 'countries')
            <div style="margin-top:var(--space-6, 24px);display:grid;gap:var(--space-3, 12px);grid-template-columns:repeat(auto-fill,minmax(220px,1fr))">
                @foreach ($countries->filter(fn ($c) => isset($perCountry[$c->id])) as $c)
                    <a href="{{ $path.'?country='.$c->iso2 }}" style="display:flex;justify-content:space-between;gap:8px;padding:var(--space-3, 12px);border:1px solid var(--color-border);border-radius:var(--radius, 8px);color:inherit;text-decoration:none">
                        <span>{{ $c->emoji() }} {{ $c->name }}</span><strong>{{ $perCountry[$c->id] }}</strong>
                    </a>
                @endforeach
            </div>
        @elseif ($partners->isEmpty())
            <p style="margin-top:var(--space-8, 32px)">{{ __('international::international.none') }}</p>
        @else
            <p style="margin-top:var(--space-4, 16px)">{{ __('international::international.found', ['count' => number_format($partners->total())]) }}</p>
            @if ($layout === 'table')
                <div style="overflow-x:auto">
                    <table class="table" style="width:100%;border-collapse:collapse">
                        <thead><tr>
                            @foreach (['name', 'country', 'agreement_type', 'expires_on'] as $col)<th style="text-align:left;padding:8px;border-bottom:2px solid var(--color-border)">{{ __('international::international.fields.'.$col) }}</th>@endforeach
                        </tr></thead>
                        <tbody>
                            @foreach ($partners as $institution)
                                <tr>
                                    <td style="padding:8px;border-bottom:1px solid var(--color-border)">@if ($institution->website_url)<a href="{{ $institution->website_url }}" target="_blank" rel="noopener">{{ $institution->name }}</a>@else{{ $institution->name }}@endif</td>
                                    <td style="padding:8px;border-bottom:1px solid var(--color-border)">{{ $institution->country?->emoji() }} {{ $institution->country?->name }}</td>
                                    <td style="padding:8px;border-bottom:1px solid var(--color-border)">{{ __('international::international.agreements.'.$institution->agreement_type) }}</td>
                                    <td style="padding:8px;border-bottom:1px solid var(--color-border)">
                                        {{ $institution->expires_on?->translatedFormat('j M Y') }}
                                        @if ($institution->isExpired()) <span style="color:var(--color-danger, #b91c1c)">({{ __('international::international.expired') }})</span>@endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(180px,1fr))">
                    @foreach ($partners as $institution)
                        @include('international::partials.institution', ['institution' => $institution])
                    @endforeach
                </div>
            @endif
            <div style="margin-top:var(--space-6, 24px)">{{ $partners->links() }}</div>
        @endif
    </div>
</x-theme::layout>
