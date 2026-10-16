{{-- Testimonials in a layout. Contract: $items, $layout (slider / grid / masonry / video) --}}
@php($style = match ($layout) {
    'slider' => 'display:grid;grid-auto-flow:column;grid-auto-columns:minmax(280px,1fr);gap:var(--space-4, 16px);overflow-x:auto;scroll-snap-type:x mandatory',
    'masonry' => 'columns:300px;column-gap:var(--space-4, 16px)',
    default => 'display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax('.($layout === 'video' ? '340px' : '280px').',1fr))',
})
<div class="testimonials testimonials--{{ $layout }}" style="{{ $style }}">
    @foreach ($items as $testimonial)
        <div style="{{ match ($layout) { 'slider' => 'scroll-snap-align:start', 'masonry' => 'break-inside:avoid;margin-bottom:var(--space-4, 16px)', default => '' } }}">
            @include('testimonials::partials.card', ['testimonial' => $testimonial, 'video' => $layout === 'video'])
        </div>
    @endforeach
</div>
