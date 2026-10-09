{{-- Frame of every homepage section (Theme Guide §8.3) --}}
@props(['section'])
<section id="{{ $section->anchor }}" @class(['home-section', 'home-section--alt' => in_array($section->style, ['alt', 'surface'], true), 'home-section--dark' => $section->style === 'dark'])>
    <div class="container">
        @if ($section->title)
            <header class="home-section__head">
                <h2 class="home-section__title">{{ $section->title }}</h2>
                @if ($section->subheading)
                    <p class="home-section__sub">{{ $section->subheading }}</p>
                @endif
            </header>
        @endif
        {{ $slot }}
    </div>
</section>
