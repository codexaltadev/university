{{--
    Hero (theme section): a full-width picture with a dark brand overlay (no picture = the brand colours), eyebrow,
    the builder's heading (else the site name) and subheading (else the tagline), two buttons.
    Contract: $section (title, subheading, content: eyebrow, button_label, button_url, button2_label, button2_url, image [url, alt])
--}}
<div @class(['hero', 'hero--photo' => $section->content['image']['url'] ?? null])>
    @if ($section->content['image']['url'] ?? null)
        <img src="{{ $section->content['image']['url'] }}" alt="{{ $section->content['image']['alt'] ?? '' }}" class="hero__bg" fetchpriority="high">
    @endif
    <div class="container hero__inner">
        @if ($section->content['eyebrow'] ?? null)<span class="hero__eyebrow">{{ $section->content['eyebrow'] }}</span>@endif
        <h1 class="hero__title">{{ $section->title ?: settings('general.site_name') }}</h1>
        @if ($text = $section->subheading ?: settings('general.tagline'))
            <p class="hero__text">{{ $text }}</p>
        @endif
        <div class="hero__actions">
            @if (filled($section->content['button_url'] ?? null) && filled($section->content['button_label'] ?? null))
                <a href="{{ $section->content['button_url'] }}" class="btn btn--accent btn--lg">{{ $section->content['button_label'] }}<x-theme::icon name="arrow-right" width="18" height="18" /></a>
            @endif
            @if (filled($section->content['button2_url'] ?? null) && filled($section->content['button2_label'] ?? null))
                <a href="{{ $section->content['button2_url'] }}" class="btn btn--on-dark btn--lg">{{ $section->content['button2_label'] }}</a>
            @endif
        </div>
    </div>
</div>
