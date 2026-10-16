{{-- Layout slot "announcement" (FR-ANW-01/07): bars (dismissible, remembered) and countdowns. Contract: $widgets (bars and countdowns, in order) --}}
<div class="announcements">
    @foreach ($widgets as $widget)
        @php($color = $widget->color_token ?: 'color-primary')
        @if ($widget->widget_type === 'bar')
            <div class="announcement-bar" role="region" aria-label="{{ __('announcement::announcement.types.bar') }}"
                 x-data="{ open: true, key: @js($widget->memoryKey()) }"
                 x-init="try { open = localStorage.getItem(key) !== 'closed' } catch (e) {}" x-show="open"
                 style="background:var(--{{ $color }});color:#fff;padding:8px 0">
                <div class="container" style="display:flex;gap:12px;align-items:center;justify-content:center;flex-wrap:wrap">
                    @if ($widget->title)<strong>{{ $widget->title }}</strong>@endif
                    @if ($widget->body)<span>{{ strip_tags((string) $widget->body) }}</span>@endif
                    @if ($href = $widget->href())<a href="{{ $href }}" style="color:#fff;text-decoration:underline">{{ $widget->button_text ?: __('announcement::announcement.more') }}</a>@endif
                    @if ($widget->is_dismissible)
                        <button type="button" x-on:click="open = false; try { localStorage.setItem(key, 'closed') } catch (e) {}" aria-label="{{ __('announcement::announcement.close') }}"
                                style="margin-inline-start:auto;background:none;border:0;color:#fff;font-size:1.3rem;cursor:pointer">&times;</button>
                    @endif
                </div>
            </div>
        @else
            {{-- Countdown: ticks every second; at the end it shows the message (or the slot leaves it out after a reload) --}}
            <div class="announcement-countdown" role="timer" aria-label="{{ $widget->title }}"
                 x-data="{ target: {{ $widget->target_at->getTimestampMs() }}, left: 0, tick() { this.left = Math.max(0, this.target - Date.now()) } }"
                 x-init="tick(); setInterval(() => tick(), 1000)"
                 style="background:var(--{{ $color }});color:#fff;padding:10px 0">
                <div class="container" style="display:flex;gap:16px;align-items:center;justify-content:center;flex-wrap:wrap">
                    @if ($widget->title)<strong>{{ $widget->title }}</strong>@endif
                    <span x-show="left > 0" style="display:inline-flex;gap:10px;font-variant-numeric:tabular-nums">
                        <span><b x-text="Math.floor(left / 86400000)">0</b> {{ __('announcement::announcement.countdown.days') }}</span>
                        <span><b x-text="String(Math.floor(left / 3600000) % 24).padStart(2, '0')">00</b> {{ __('announcement::announcement.countdown.hours') }}</span>
                        <span><b x-text="String(Math.floor(left / 60000) % 60).padStart(2, '0')">00</b> {{ __('announcement::announcement.countdown.minutes') }}</span>
                        <span><b x-text="String(Math.floor(left / 1000) % 60).padStart(2, '0')">00</b> {{ __('announcement::announcement.countdown.seconds') }}</span>
                    </span>
                    <span x-show="left <= 0" x-cloak>{{ $widget->after_end === 'message' ? ($widget->after_end_text ?: __('announcement::announcement.countdown.started')) : '' }}</span>
                    <noscript>{{ $widget->target_at->translatedFormat('j F Y, H:i') }}</noscript>
                    @if ($href = $widget->href())<a href="{{ $href }}" style="color:#fff;text-decoration:underline">{{ $widget->button_text ?: __('announcement::announcement.more') }}</a>@endif
                </div>
            </div>
        @endif
    @endforeach
</div>
