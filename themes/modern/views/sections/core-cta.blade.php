{{-- Core call to action (core.cta): the builder's heading and subheading with one button, as a band. Contract: $items ([label, url]), $section --}}
<div class="container">
    <div class="cta-band">
        <div>
            @if ($section->title)<h2>{{ $section->title }}</h2>@endif
            @if ($section->subheading)<p>{{ $section->subheading }}</p>@endif
        </div>
        @if (($items['url'] ?? null) && ($items['label'] ?? null))
            <div class="cta-band__actions"><a href="{{ $items['url'] }}" class="btn btn--primary">{{ $items['label'] }}</a></div>
        @endif
    </div>
</div>
