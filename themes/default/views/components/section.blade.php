{{--
    Frame of every homepage section (Theme Guide §8.3). The theme's own sections (theme.json
    "sections") and module sections this theme redraws (views/modules/…) are full-width DIU
    blocks with their own container and heading: they only get the anchor. Any other section
    gets the container and its heading.
--}}
@props(['section'])
@if (str_starts_with((string) $section->key, 'theme.') || view()->exists('theme::modules.'.str_replace('::', '.', (string) $section->view)))
    <div id="{{ $section->anchor }}" class="home-block">{{ $slot }}</div>
@else
    <section id="{{ $section->anchor }}" @class(['home-section', 'home-section--alt' => in_array($section->style, ['alt', 'surface', 'light'], true), 'home-section--dark' => in_array($section->style, ['dark', 'primary'], true)])>
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
@endif
