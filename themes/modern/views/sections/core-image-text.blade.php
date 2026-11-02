{{-- Core picture and text (core.image-text): side by side, the picture left or right. Contract: $items ([image, alt, position, body]), $section --}}
<div class="container split items-center">
    @if ($items['image'] ?? null)
        <div @class(['overflow-hidden rounded-lg', 'lg:order-last' => ($items['position'] ?? 'left') === 'right'])>
            <img src="{{ $items['image'] }}" alt="{{ $items['alt'] }}" class="w-full object-cover" loading="lazy">
        </div>
    @endif
    <div>
        @if ($section->title)<h2 class="section-head__title mb-4">{{ $section->title }}</h2>@endif
        @if ($section->subheading)<p class="lead mb-4">{{ $section->subheading }}</p>@endif
        <div class="prose">{!! $items['body'] ?? '' !!}</div>
    </div>
</div>
