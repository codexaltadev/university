{{-- Layout slot "ticker" (FR-ANW-01/06): every ticker line of this page, moving; pauses on hover / focus, stands still with reduced motion. Contract: $items (list of text, url) --}}
<div class="announcement-ticker" role="region" aria-label="{{ __('announcement::announcement.types.ticker') }}" x-data="{ paused: false }"
     x-on:mouseenter="paused = true" x-on:mouseleave="paused = false" x-on:focusin="paused = true" x-on:focusout="paused = false"
     style="background:var(--color-surface, #F3F4F9);border-bottom:1px solid var(--color-border)">
    <div class="container" style="display:flex;gap:12px;align-items:center;overflow:hidden;padding-block:6px">
        <strong style="flex:none;color:var(--color-primary)">{{ __('announcement::announcement.ticker_label') }}</strong>
        <div style="overflow:hidden;flex:1">
            <div class="announcement-ticker__track" :style="{ animationPlayState: paused ? 'paused' : 'running' }"
                 style="display:inline-flex;gap:40px;white-space:nowrap;animation:anw-ticker {{ max(20, count($items) * 8) }}s linear infinite">
                @foreach ($items as $item)
                    @if ($item['url'])<a href="{{ $item['url'] }}">{{ $item['text'] }}</a>@else<span>{{ $item['text'] }}</span>@endif
                @endforeach
            </div>
        </div>
    </div>
    <style>@keyframes anw-ticker { from { transform: translateX(100%) } to { transform: translateX(-100%) } } @media (prefers-reduced-motion: reduce) { .announcement-ticker__track { animation: none !important; white-space: normal !important; flex-wrap: wrap } }</style>
</div>
