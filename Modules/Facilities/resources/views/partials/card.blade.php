{{-- One facility. Contract: $facility, $base (microsite path or ''), $layout (icons / cards / list) --}}
@php($href = $facility->href($base))
@php($icon = $facility->firstMedia('icon'))
@php($image = $facility->firstMedia('image'))
<article class="facility facility--{{ $layout }}" style="{{ match ($layout) {
    'icons' => 'text-align:center;padding:var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius, 8px);height:100%',
    'list' => 'display:flex;gap:var(--space-3, 12px);align-items:center;padding:var(--space-3, 12px) 0;border-bottom:1px solid var(--color-border)',
    default => 'border:1px solid var(--color-border);border-radius:var(--radius, 8px);overflow:hidden;height:100%',
} }}">
    @if ($layout === 'cards' && $image)
        <img src="{{ $image->url('medium') ?? $image->url() }}" alt="{{ $image->alt_text ?: $facility->title }}" loading="lazy" style="width:100%;aspect-ratio:16/10;object-fit:cover">
    @elseif ($icon)
        <img src="{{ $icon->url('thumb') ?? $icon->url() }}" alt="" loading="lazy" style="width:{{ $layout === 'list' ? '32px' : '56px' }};height:{{ $layout === 'list' ? '32px' : '56px' }};object-fit:contain">
    @endif
    <div style="{{ $layout === 'cards' ? 'padding:var(--space-4, 16px)' : '' }}">
        <h3 style="margin:var(--space-2, 8px) 0 4px;font-size:1.05rem">
            @if ($href)<a href="{{ $href }}" @if (! $facility->hasPage() && $facility->open_in_new_tab) target="_blank" rel="noopener" @endif>{{ $facility->title }}</a>@else{{ $facility->title }}@endif
        </h3>
        @if ($facility->summary && $layout !== 'icons')<p style="margin:0">{{ $facility->summary }}</p>@endif
    </div>
</article>
