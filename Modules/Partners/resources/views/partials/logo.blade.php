{{-- One partner's logo card. Contract: $partner --}}
@php($logo = $partner->firstMedia('logo'))
@php($tag = $partner->website_url ? 'a' : 'div')
<{{ $tag }} class="partner" @if ($partner->website_url) href="{{ $partner->website_url }}" target="_blank" rel="noopener" @endif title="{{ $partner->name }}"
    style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:var(--space-2, 8px);padding:var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius, 8px);height:100%;color:inherit;text-decoration:none;text-align:center">
    @if ($logo)
        <img src="{{ $logo->url('medium') ?? $logo->url() }}" alt="{{ $logo->alt_text ?: $partner->name }}" loading="lazy" style="max-width:100%;max-height:72px;object-fit:contain">
    @else
        <strong>{{ $partner->name }}</strong>
    @endif
    @if ($logo)<span style="font-size:.85rem">{{ $partner->name }}</span>@endif
</{{ $tag }}>
