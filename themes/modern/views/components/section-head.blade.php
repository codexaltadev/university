{{-- <x-theme::section-head :title="…" :sub="…" eyebrow="…" :link="url" :link-label="…" /> — a section's heading row --}}
@props(['title' => null, 'sub' => null, 'eyebrow' => null, 'link' => null, 'linkLabel' => null])
@if ($title || $eyebrow || $link)
    <header class="section-head">
        <div>
            @if ($eyebrow)<span class="eyebrow">{{ $eyebrow }}</span>@endif
            @if ($title)<h2 class="section-head__title">{{ $title }}</h2>@endif
            @if ($sub)<p class="section-head__sub">{{ $sub }}</p>@endif
        </div>
        @if ($link)
            <a href="{{ $link }}" class="btn">{{ $linkLabel ?: __('theme::theme.view_all') }}<x-theme::icon name="arrow-right" width="16" height="16" /></a>
        @endif
    </header>
@endif
