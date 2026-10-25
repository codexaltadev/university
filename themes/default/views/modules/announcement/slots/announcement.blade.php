{{--
    Layout slot "announcement" (above the header) in DIU's colours — DIU has no bar or countdown of its own, so
    they follow its greeting popup: the navy → blue gradient (or the colour chosen), white text, a white
    pill button. Bar: title, text, button, × (closed bars stay closed in this browser). Countdown: title,
    days / hours / minutes / seconds as tiles, the message at the end. Each kind only while it is on in
    Settings › Announcements. Above the homepage hero, which slides under the floating header (z-40, the header z-50).
    Contract: $widgets (bars and countdowns, in order)
--}}
<div class="announcements relative z-40">
    @foreach ($widgets as $widget)
        @if ($widget->widget_type === 'bar')
            <div role="region" aria-label="{{ __('announcement::announcement.types.bar') }}"
                 x-data="{ open: true, key: @js($widget->memoryKey()) }"
                 x-init="try { open = localStorage.getItem(key) !== 'closed' } catch (e) {}" x-show="open"
                 @class(['announcement-bar text-white', 'bg-gradient-to-r from-secondary via-primary to-secondary' => ! $widget->color_token])
                 @if ($widget->color_token) style="background:var(--{{ $widget->color_token }})" @endif>
                <div class="container mx-auto flex items-center gap-3 py-2.5 text-sm">
                    <x-theme::icon name="megaphone" class="hidden sm:block w-5 h-5 shrink-0 text-white/80" />
                    <p class="flex-1 min-w-0 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-center m-0">
                        @if ($widget->title)<strong class="font-bold">{{ $widget->title }}</strong>@endif
                        @if ($widget->body)<span class="text-white/90">{{ strip_tags((string) $widget->body) }}</span>@endif
                        @if ($href = $widget->href())
                            <a href="{{ $href }}" class="inline-flex items-center gap-1 rounded-full bg-white px-3.5 py-1 text-xs font-bold text-secondary hover:no-underline hover:bg-white/90">{{ $widget->button_text ?: __('announcement::announcement.more') }} <x-theme::icon name="arrow-right" class="w-3.5 h-3.5" /></a>
                        @endif
                    </p>
                    @if ($widget->is_dismissible)
                        <button type="button" x-on:click="open = false; try { localStorage.setItem(key, 'closed') } catch (e) {}" aria-label="{{ __('announcement::announcement.close') }}"
                                class="shrink-0 w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/15 transition-colors"><x-theme::icon name="close" class="w-4 h-4" /></button>
                    @endif
                </div>
            </div>
        @else
            {{-- Countdown: ticks every second; at the end it shows the message (or the slot leaves it out after a reload) --}}
            <div role="timer" aria-label="{{ $widget->title }}"
                 x-data="{ target: {{ $widget->target_at->getTimestampMs() }}, left: 0, tick() { this.left = Math.max(0, this.target - Date.now()) } }"
                 x-init="tick(); setInterval(() => tick(), 1000)"
                 @class(['announcement-countdown text-white', 'bg-gradient-to-r from-secondary via-primary to-secondary' => ! $widget->color_token])
                 @if ($widget->color_token) style="background:var(--{{ $widget->color_token }})" @endif>
                <div class="container mx-auto flex flex-wrap items-center justify-center gap-x-6 gap-y-2 py-3">
                    @if ($widget->title)<strong class="flex items-center gap-2 text-base sm:text-lg font-bold"><x-theme::icon name="clock" class="w-5 h-5 text-white/80" />{{ $widget->title }}</strong>@endif
                    <span x-show="left > 0" class="flex items-center gap-2 tabular-nums">
                        @foreach ([
                            ['x' => 'Math.floor(left / 86400000)', 'label' => __('announcement::announcement.countdown.days')],
                            ['x' => "String(Math.floor(left / 3600000) % 24).padStart(2, '0')", 'label' => __('announcement::announcement.countdown.hours')],
                            ['x' => "String(Math.floor(left / 60000) % 60).padStart(2, '0')", 'label' => __('announcement::announcement.countdown.minutes')],
                            ['x' => "String(Math.floor(left / 1000) % 60).padStart(2, '0')", 'label' => __('announcement::announcement.countdown.seconds')],
                        ] as $unit)
                            <span class="flex flex-col items-center min-w-12 rounded-lg bg-white/10 border border-white/15 px-2 py-1">
                                <b class="text-lg leading-none font-extrabold" x-text="{{ $unit['x'] }}">0</b>
                                <span class="text-[10px] uppercase tracking-wide text-white/75">{{ $unit['label'] }}</span>
                            </span>
                        @endforeach
                    </span>
                    <span x-show="left <= 0" x-cloak class="font-semibold">{{ $widget->after_end === 'message' ? ($widget->after_end_text ?: __('announcement::announcement.countdown.started')) : '' }}</span>
                    <noscript>{{ $widget->target_at->translatedFormat('j F Y, H:i') }}</noscript>
                    @if ($href = $widget->href())
                        <a href="{{ $href }}" class="inline-flex items-center gap-1 rounded-full bg-white px-4 py-1.5 text-xs font-bold text-secondary hover:no-underline hover:bg-white/90">{{ $widget->button_text ?: __('announcement::announcement.more') }} <x-theme::icon name="arrow-right" class="w-3.5 h-3.5" /></a>
                    @endif
                </div>
            </div>
        @endif
    @endforeach
</div>
