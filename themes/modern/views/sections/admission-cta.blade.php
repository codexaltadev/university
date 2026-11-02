{{-- Call to action band (theme section): eyebrow, the builder's heading and subheading, two buttons. Contract: $section (title, subheading, content: eyebrow, button_label, button_url, button2_label, button2_url) --}}
<div class="container">
    <div class="cta-band">
        <div>
            @if ($section->content['eyebrow'] ?? null)<span class="eyebrow">{{ $section->content['eyebrow'] }}</span>@endif
            @if ($section->title)<h2>{{ $section->title }}</h2>@endif
            @if ($section->subheading)<p>{{ $section->subheading }}</p>@endif
        </div>
        <div class="cta-band__actions">
            @if (filled($section->content['button_url'] ?? null) && filled($section->content['button_label'] ?? null))
                <a href="{{ $section->content['button_url'] }}" class="btn btn--primary">{{ $section->content['button_label'] }}</a>
            @endif
            @if (filled($section->content['button2_url'] ?? null) && filled($section->content['button2_label'] ?? null))
                <a href="{{ $section->content['button2_url'] }}" class="btn btn--on-dark">{{ $section->content['button2_label'] }}</a>
            @endif
        </div>
    </div>
</div>
