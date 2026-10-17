{{-- One ranking result card. Contract: $ranking --}}
@php($badge = $ranking->firstMedia('badge') ?? $ranking->firstMedia('logo'))
<article id="{{ $ranking->agency_key }}" class="ranking" style="border:1px solid var(--color-border);border-radius:var(--radius, 8px);padding:var(--space-4, 16px);text-align:center;height:100%;display:flex;flex-direction:column;gap:6px;align-items:center">
    @if ($badge)<img src="{{ $badge->url('medium') ?? $badge->url() }}" alt="" loading="lazy" style="max-width:100%;height:72px;object-fit:contain">@endif
    <strong style="font-size:1.6rem;color:var(--color-primary)">{{ $ranking->rank_label }}</strong>
    <span>{{ $ranking->label() }}</span>
    <span style="font-size:.85rem;opacity:.8">{{ $ranking->year }}</span>
    @if ($ranking->summary)<p style="margin:0;font-size:.9rem">{{ $ranking->summary }}</p>@endif
    @if ($ranking->ranking_url)<a href="{{ $ranking->ranking_url }}" target="_blank" rel="noopener" style="margin-top:auto">{{ __('accreditation::accreditation.see_ranking') }}</a>@endif
</article>
