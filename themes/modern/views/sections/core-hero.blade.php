{{--
    Core hero / slider (FR-HOME-02, core.hero): each slide = picture, title, text, button; several slides rotate
    (paused for reduced motion, on hover and focus). Contract: $items ([image, alt, title, text, button: [label, url]]), $section
--}}
@if (collect($items ?? [])->isNotEmpty())
    <div class="hero hero-slides" x-data="heroSlides({{ count($items) }})" @mouseenter="stop()" @mouseleave="play()" @focusin="stop()">
        @foreach ($items as $slide)
            <div class="container hero__grid" @if (! $loop->first) x-cloak @endif x-show="current === {{ $loop->index }}" x-transition.opacity.duration.400ms
                 role="group" aria-roledescription="slide" aria-label="{{ __('theme::theme.slide', ['number' => $loop->iteration]) }}">
                <div>
                    @if ($loop->first)
                        <h1 class="hero__title">{{ $slide['title'] ?: ($section->title ?: settings('general.site_name')) }}</h1>
                    @else
                        <h2 class="hero__title">{{ $slide['title'] }}</h2>
                    @endif
                    @if ($slide['text'] ?? null)<p class="hero__text">{{ $slide['text'] }}</p>@endif
                    @if (($slide['button']['url'] ?? null) && ($slide['button']['label'] ?? null))
                        <div class="hero__actions"><a href="{{ $slide['button']['url'] }}" class="btn btn--primary">{{ $slide['button']['label'] }}<x-theme::icon name="arrow-right" width="16" height="16" /></a></div>
                    @endif
                </div>
                <div class="hero__media"><img src="{{ $slide['image'] }}" alt="{{ $slide['alt'] }}" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif></div>
            </div>
        @endforeach
        @if (count($items) > 1)
            <div class="container hero-dots">
                @foreach ($items as $slide)
                    <button type="button" @click="go({{ $loop->index }})" :aria-current="current === {{ $loop->index }}"><span class="sr-only">{{ __('theme::theme.slide', ['number' => $loop->iteration]) }}</span></button>
                @endforeach
            </div>
        @endif
    </div>
@endif
