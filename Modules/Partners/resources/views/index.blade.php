{{-- /partners and /{entity}/partners (FR-PRT-02). Contract: $partners, $types (types present), $type (chosen or null), $hasDiscounts, $base --}}
@php($path = url($base.'/'.\Modules\Partners\Models\Partner::PATH))
<x-theme::layout :title="__('partners::partners.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('partners::partners.plural') }}</h1>
        @if ($types->count() > 1 || $hasDiscounts)
            <nav style="display:flex;flex-wrap:wrap;gap:var(--space-2, 8px);margin-top:var(--space-4, 16px)">
                @if ($types->count() > 1)
                    <a href="{{ $path }}" @class(['btn', 'btn--outline' => $type !== null])>{{ __('partners::partners.all') }}</a>
                    @foreach ($types as $t)
                        <a href="{{ $path.'?type='.$t }}" @class(['btn', 'btn--outline' => $type !== $t])>{{ __('partners::partners.types.'.$t) }}</a>
                    @endforeach
                @endif
                @if ($hasDiscounts)<a class="btn btn--outline" href="{{ url($base.'/'.\Modules\Partners\Models\Partner::DISCOUNT_PATH) }}">{{ __('partners::partners.see_discounts') }}</a>@endif
            </nav>
        @endif
        @if ($partners->isEmpty())
            <p style="margin-top:var(--space-8, 32px)">{{ __('partners::partners.none') }}</p>
        @else
            <div style="margin-top:var(--space-6, 24px);display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(180px,1fr))">
                @foreach ($partners as $partner)
                    @include('partners::partials.logo', ['partner' => $partner])
                @endforeach
            </div>
        @endif
    </div>
</x-theme::layout>
