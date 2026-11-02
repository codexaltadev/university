{{--
    Hero (theme section): eyebrow, the builder's heading (else the site name) and subheading (else the tagline),
    two buttons and a picture. Contract: $section (title, subheading, content: eyebrow, button_label, button_url,
    button2_label, button2_url, image [url, alt])
--}}
<div class="hero">
    <div class="container hero__grid">
        <div>
            @if ($section->content['eyebrow'] ?? null)<span class="eyebrow">{{ $section->content['eyebrow'] }}</span>@endif
            <h1 class="hero__title">{{ $section->title ?: settings('general.site_name') }}</h1>
            @if ($text = $section->subheading ?: settings('general.tagline'))
                <p class="hero__text">{{ $text }}</p>
            @endif
            <div class="hero__actions">
                @if (filled($section->content['button_url'] ?? null) && filled($section->content['button_label'] ?? null))
                    <a href="{{ $section->content['button_url'] }}" class="btn btn--primary">{{ $section->content['button_label'] }}<x-theme::icon name="arrow-right" width="16" height="16" /></a>
                @endif
                @if (filled($section->content['button2_url'] ?? null) && filled($section->content['button2_label'] ?? null))
                    <a href="{{ $section->content['button2_url'] }}" class="btn">{{ $section->content['button2_label'] }}</a>
                @endif
            </div>
        </div>
        @if ($section->content['image']['url'] ?? null)
            <div class="hero__media"><img src="{{ $section->content['image']['url'] }}" alt="{{ $section->content['image']['alt'] ?? '' }}" fetchpriority="high"></div>
        @endif
    </div>
</div>
