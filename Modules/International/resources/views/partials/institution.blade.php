{{-- One partner institution's logo card. Contract: $institution --}}
@php($logo = $institution->firstMedia('logo'))
@php($tag = $institution->website_url ? 'a' : 'div')
<{{ $tag }} class="intl-partner" @if ($institution->website_url) href="{{ $institution->website_url }}" target="_blank" rel="noopener" @endif title="{{ $institution->name }}"
    style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;padding:var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius, 8px);height:100%;color:inherit;text-decoration:none;text-align:center">
    @if ($logo)<img src="{{ $logo->url('medium') ?? $logo->url() }}" alt="{{ $logo->alt_text ?: $institution->name }}" loading="lazy" style="max-width:100%;max-height:64px;object-fit:contain">@endif
    <span style="font-size:.9rem;font-weight:600">{{ $institution->name }}</span>
    @if ($institution->country)<span style="font-size:.8rem;opacity:.75">{{ $institution->country->emoji() }} {{ $institution->country->name }}</span>@endif
    @if ($institution->isExpired())<span style="font-size:.75rem;padding:1px 8px;border-radius:999px;background:var(--color-danger, #b91c1c);color:#fff">{{ __('international::international.expired') }}</span>@endif
</{{ $tag }}>
