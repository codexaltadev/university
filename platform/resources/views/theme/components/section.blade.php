{{-- Kernel default frame of a homepage section; themes draw their own (Theme Guide §8.3) --}}
@props(['section'])
<section id="{{ $section->anchor }}" class="home-section">
    @if ($section->title)
        <h2>{{ $section->title }}</h2>
    @endif
    @if ($section->subheading)
        <p>{{ $section->subheading }}</p>
    @endif
    {{ $slot }}
</section>
