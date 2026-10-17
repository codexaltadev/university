{{--
    Homepage section "Hero" (FR-SLD-01..06). Contract: $items (Slide list: one = a still hero, several = a slider), $section,
    $settings (autoplay, interval, transition, arrows, dots, height). A slide with a video plays it full-screen behind the text
    (autoplay, muted, loop, playsinline; the picture is the poster); not on phones unless asked, never with reduced motion.
--}}
@if ($items->isNotEmpty())
    @php
        $many = $items->count() > 1;
        $height = ['screen' => '100svh', 'large' => '75vh', 'medium' => '50vh'][$settings['height'] ?? 'large'] ?? '75vh';
        $fade = ($settings['transition'] ?? 'slide') === 'fade';
        $interval = max(3, (int) ($settings['interval'] ?? 6)) * 1000;
        $autoplay = $many && ($settings['autoplay'] ?? true);
    @endphp
    <div class="hero-slider" role="region" aria-roledescription="{{ $many ? __('slider::slider.carousel') : '' }}" aria-label="{{ __('slider::slider.section.label') }}"
         style="position:relative;overflow:hidden;min-height:{{ $height }};background:var(--color-primary, #002652)"
         x-data="{ i: 0, n: {{ $items->count() }}, timer: null, still: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
                   go(k) { this.i = (k + this.n) % this.n },
                   play() { if ({{ $autoplay ? 'true' : 'false' }} && ! this.still) this.timer = setInterval(() => this.go(this.i + 1), {{ $interval }}) },
                   stop() { clearInterval(this.timer) } }"
         x-init="play()" x-on:mouseenter="stop()" x-on:mouseleave="play()" x-on:focusin="stop()">
        <style>
            @media (max-width: 767px) { .hero-slide video[data-desktop-only] { display: none } }
            @media (prefers-reduced-motion: reduce) { .hero-slide video { display: none } .hero-slide { transition: none !important } }
        </style>
        @foreach ($items as $k => $slide)
            @php
                $desktop = $slide->desktopImage();
                $mobile = $slide->mobileImage();
                $video = $slide->videoItem();
                $align = ['left' => 'flex-start', 'center' => 'center', 'right' => 'flex-end'][$slide->text_position] ?? 'flex-start';
                $opacity = max(0, min(80, (int) $slide->overlay_opacity)) / 100;
            @endphp
            <div class="hero-slide" @if ($many) x-show="i === {{ $k }}" @if ($fade) x-transition.opacity.duration.700ms @endif aria-roledescription="{{ __('slider::slider.slide') }}" aria-label="{{ $k + 1 }} / {{ $items->count() }}" @endif
                 style="position:{{ $k === 0 ? 'relative' : 'absolute' }};inset:0;min-height:{{ $height }};display:flex;align-items:flex-end;{{ $k === 0 ? '' : 'display:none;' }}">
                @if ($desktop)
                    <picture>
                        @if ($mobile && $mobile->isNot($desktop))<source media="(max-width: 767px)" srcset="{{ $mobile->url('large') ?? $mobile->url() }}">@endif
                        <img src="{{ $desktop->url('large') ?? $desktop->url() }}" alt="{{ $desktop->alt_text ?: (string) $slide->title }}" @if ($k > 0) loading="lazy" @endif
                             style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">
                    </picture>
                @endif
                @if ($video)
                    <video autoplay muted loop playsinline preload="{{ $k === 0 ? 'auto' : 'none' }}" poster="{{ $desktop?->url('large') }}" @unless ($slide->video_on_mobile) data-desktop-only @endunless
                           aria-hidden="true" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">
                        <source src="{{ $video->url() }}" type="{{ $video->mime_type }}">
                    </video>
                @endif
                <div aria-hidden="true" style="position:absolute;inset:0;background:linear-gradient(180deg, rgba(0,0,0,0) 35%, color-mix(in srgb, var(--color-primary, #002652) {{ (int) ($opacity * 100 + 20) }}%, transparent) 100%), rgba(0,0,0,{{ $opacity / 2 }})"></div>
                <div class="container" style="position:relative;width:100%;display:flex;justify-content:{{ $align }};padding-block:var(--space-12, 48px);color:#fff">
                    <div style="max-width:720px;text-align:{{ $slide->text_position }}">
                        @if ($slide->title)<h2 style="font-size:clamp(1.6rem, 4vw, 3rem);line-height:1.15;margin:0">{{ $slide->title }}</h2>@endif
                        @if ($slide->subtitle)<p style="font-size:1.15rem;margin-top:var(--space-3, 12px)">{{ $slide->subtitle }}</p>@endif
                        @if ($bullets = $slide->bulletList())
                            <ul style="margin-top:var(--space-3, 12px);padding-inline-start:1.2em">@foreach ($bullets as $b)<li>{{ $b }}</li>@endforeach</ul>
                        @endif
                        @if ($buttons = $slide->buttonList())
                            <p style="display:flex;flex-wrap:wrap;gap:var(--space-3, 12px);margin-top:var(--space-5, 20px);justify-content:{{ $align }}">
                                @foreach ($buttons as $n => $button)
                                    <a href="{{ $button['url'] }}" @class(['btn', 'btn--outline' => $n > 0])>{{ $button['label'] }}</a>
                                @endforeach
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        @if ($many)
            @if ($settings['arrows'] ?? true)
                <button type="button" x-on:click="stop(); go(i - 1)" aria-label="{{ __('slider::slider.previous') }}" style="position:absolute;inset-inline-start:12px;top:50%;font-size:2.5rem;color:#fff;background:none;border:0;cursor:pointer">&#8249;</button>
                <button type="button" x-on:click="stop(); go(i + 1)" aria-label="{{ __('slider::slider.next') }}" style="position:absolute;inset-inline-end:12px;top:50%;font-size:2.5rem;color:#fff;background:none;border:0;cursor:pointer">&#8250;</button>
            @endif
            @if ($settings['dots'] ?? true)
                <div style="position:absolute;bottom:14px;inset-inline:0;display:flex;justify-content:center;gap:8px">
                    @foreach ($items as $k => $slide)
                        <button type="button" x-on:click="stop(); go({{ $k }})" aria-label="{{ __('slider::slider.go_to', ['n' => $k + 1]) }}" x-bind:aria-current="i === {{ $k }}"
                                x-bind:style="'width:10px;height:10px;border-radius:50%;border:0;cursor:pointer;background:' + (i === {{ $k }} ? '#fff' : 'rgba(255,255,255,.45)')"></button>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
@endif
