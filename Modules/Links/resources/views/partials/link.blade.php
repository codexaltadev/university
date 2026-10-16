{{-- One link. Contract: $link, $layout (tiles / list / footer) --}}
@php($icon = $link->firstMedia('icon'))
<a href="{{ $link->href() }}" class="important-link important-link--{{ $layout }}" @if ($link->open_in_new_tab) target="_blank" rel="noopener" @endif
   style="{{ match ($layout) {
       'tiles' => 'display:flex;flex-direction:column;align-items:center;gap:var(--space-2, 8px);padding:var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius, 8px);text-align:center;text-decoration:none;color:inherit;height:100%',
       'list' => 'display:flex;gap:var(--space-3, 12px);align-items:center;padding:var(--space-2, 8px) 0;border-bottom:1px solid var(--color-border);text-decoration:none;color:inherit',
       default => 'display:flex;gap:var(--space-2, 8px);align-items:center',
   } }}">
    @if ($icon)
        <img src="{{ $icon->url('thumb') ?? $icon->url() }}" alt="" loading="lazy" style="{{ $layout === 'tiles' ? 'width:48px;height:48px' : 'width:22px;height:22px' }};object-fit:contain">
    @endif
    <span>
        <strong style="{{ $layout === 'footer' ? 'font-weight:inherit' : '' }}">{{ $link->title }}</strong>
        @if ($layout !== 'footer' && $link->description)<br><small style="color:var(--color-muted, #6b7280)">{{ $link->description }}</small>@endif
    </span>
    @if ($link->open_in_new_tab)<span class="sr-only">{{ __('links::links.new_tab') }}</span>@endif
</a>
