{{-- Homepage section "Notice Ticker" (FR-NOT-06). Contract: $items (Notice list, urgent first), $section, $settings; $context from the page --}}
@if ($items->isNotEmpty())
    @php($base = isset($context) && $context->isMicrosite() ? rtrim((string) app(\Platform\Context\MicrositeResolver::class)->urlFor($context->entity), '/') : '')
    <div class="notice-ticker" role="region" aria-label="{{ __('notice::notice.section.ticker') }}" x-data="{ paused: false }" x-on:mouseenter="paused = true" x-on:mouseleave="paused = false"
         style="display:flex;gap:var(--space-3, 12px);align-items:center;overflow:hidden;padding:var(--space-2, 8px) var(--space-3, 12px);background:var(--color-surface, #F3F4F9);border-radius:var(--radius-sm, 4px)">
        <strong style="flex:none">{{ __('notice::notice.plural') }}</strong>
        <div style="overflow:hidden;flex:1">
            <div class="notice-ticker__track" :style="{ animationPlayState: paused ? 'paused' : 'running' }" style="display:inline-flex;gap:var(--space-8, 32px);white-space:nowrap;animation:notice-ticker 30s linear infinite">
                @foreach ($items as $notice)
                    <a href="{{ url($base.'/'.\Modules\Notice\Models\Notice::PATH.'/'.$notice->slug()) }}" @style(['font-weight:700;color:var(--color-danger, #DC2626)' => $notice->is_urgent])>{{ $notice->title }}</a>
                @endforeach
            </div>
        </div>
    </div>
    <style>@keyframes notice-ticker { from { transform: translateX(100%) } to { transform: translateX(-100%) } } @media (prefers-reduced-motion: reduce) { .notice-ticker__track { animation: none !important; white-space: normal !important; flex-wrap: wrap } }</style>
@endif
