{{--
    Layout slot "ticker" (under the header) in DIU's colours — DIU has no ticker of its own: a navy strip, the
    label (Settings › Announcements, "Latest") as a blue tab with a megaphone, the lines moving with a dot
    between them (shown twice for an endless run); pauses on hover / focus, stands still with reduced
    motion. Only while tickers are on in Settings › Announcements.
    Contract: $items (list of text, url), $label (Settings)
--}}
<div class="announcement-ticker relative z-40 bg-secondary text-white" role="region" aria-label="{{ __('announcement::announcement.types.ticker') }}"
     x-data="{ paused: false }" x-on:mouseenter="paused = true" x-on:mouseleave="paused = false" x-on:focusin="paused = true" x-on:focusout="paused = false">
    <div class="container mx-auto flex items-stretch overflow-hidden">
        <strong class="relative z-10 flex items-center gap-2 shrink-0 bg-primary px-4 py-2 text-sm font-bold uppercase tracking-wide"><x-theme::icon name="megaphone" class="w-4 h-4" />{{ $label ?: __('announcement::announcement.ticker_label') }}</strong>
        <div class="announcement-ticker__window flex-1 overflow-hidden">
            <div class="announcement-ticker__track flex w-max py-2 text-sm" :style="{ animationPlayState: paused ? 'paused' : 'running' }" style="--anw-duration: {{ max(20, count($items) * 10) }}s">
                @foreach ([false, true] as $copy)
                    <span class="flex shrink-0" @if ($copy) aria-hidden="true" @endif>
                        @foreach ($items as $item)
                            <span class="flex items-center gap-6 pl-6">
                                @if ($item['url'])<a href="{{ $item['url'] }}" @if ($copy) tabindex="-1" @endif class="text-white hover:text-white/80 hover:underline whitespace-nowrap">{{ $item['text'] }}</a>@else<span class="whitespace-nowrap">{{ $item['text'] }}</span>@endif
                                <span class="w-1.5 h-1.5 rounded-full bg-white/50" aria-hidden="true"></span>
                            </span>
                        @endforeach
                    </span>
                @endforeach
            </div>
        </div>
    </div>
</div>
