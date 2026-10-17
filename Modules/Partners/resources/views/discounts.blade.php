{{-- /student-discounts (FR-PRT-02/04). Contract: $groups (['term' => ?Term, 'partners' => list]), $base --}}
<x-theme::layout :title="__('partners::partners.discounts')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('partners::partners.discounts') }}</h1>
        @if ($groups === [])
            <p style="margin-top:var(--space-8, 32px)">{{ __('partners::partners.no_discounts') }}</p>
        @endif
        @if (count($groups) > 1)
            <nav style="display:flex;flex-wrap:wrap;gap:var(--space-2, 8px);margin-top:var(--space-4, 16px)">
                @foreach ($groups as $i => $group)
                    <a class="button button--outline" href="#discounts-{{ $i }}">{{ $group['term']?->name ?? __('partners::partners.other_discounts') }}</a>
                @endforeach
            </nav>
        @endif
        @foreach ($groups as $i => $group)
            <section id="discounts-{{ $i }}" style="margin-top:var(--space-8, 32px)">
                <h2 style="display:flex;align-items:center;gap:var(--space-2, 8px)">
                    @if ($group['term']?->icon && ($svg = rescue(fn () => svg($group['term']->icon, '', ['style' => 'width:1.5em;height:1.5em', 'aria-hidden' => 'true'])->toHtml(), null, false)))
                        {!! $svg !!}
                    @endif
                    {{ $group['term']?->name ?? __('partners::partners.other_discounts') }}
                </h2>
                <div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
                    @foreach ($group['partners'] as $partner)
                        @php($logo = $partner->firstMedia('logo'))
                        <article style="border:1px solid var(--color-border);border-radius:var(--radius, 8px);padding:var(--space-4, 16px);display:flex;flex-direction:column;gap:var(--space-2, 8px)">
                            <div style="display:flex;gap:var(--space-3, 12px);align-items:center">
                                @if ($logo)<img src="{{ $logo->url('thumb') ?? $logo->url() }}" alt="" loading="lazy" style="width:56px;height:56px;object-fit:contain">@endif
                                <h3 style="margin:0;font-size:1.05rem">{{ $partner->name }}</h3>
                            </div>
                            @if ($partner->discount_value)<p style="margin:0;font-size:1.25rem;font-weight:700;color:var(--color-primary)">{{ $partner->discount_value }}</p>@endif
                            @if ($partner->description)<p style="margin:0">{{ $partner->description }}</p>@endif
                            @if ($partner->discount_terms)<p style="margin:0;font-size:.85rem;opacity:.8">{{ $partner->discount_terms }}</p>@endif
                            @if ($partner->valid_until)<p style="margin:0;font-size:.85rem">{{ __('partners::partners.valid_until', ['date' => $partner->valid_until->translatedFormat('j F Y')]) }}</p>@endif
                            @if ($partner->website_url)<p style="margin:auto 0 0"><a href="{{ $partner->website_url }}" target="_blank" rel="noopener">{{ __('partners::partners.visit') }}</a></p>@endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-theme::layout>
