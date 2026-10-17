{{-- DIU's SoftSlide: the content fades and slides in once it scrolls into view. direction: up (default), left, right; delay in seconds --}}
@props(['direction' => 'up', 'delay' => 0])
<div x-data x-intersect.once="$el.classList.add('is-visible')"
     style="--reveal-x: {{ $direction === 'left' ? '-30px' : ($direction === 'right' ? '30px' : '0') }}; --reveal-y: {{ $direction === 'up' ? '40px' : '20px' }}; --reveal-delay: {{ (float) $delay }}s"
     {{ $attributes->class(['reveal']) }}>{{ $slot }}</div>
