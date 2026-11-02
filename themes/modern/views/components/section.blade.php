{{--
    Frame of every homepage section (Theme Guide §8.3). Sections this theme draws itself (its theme.json
    sections, the core sections and module sections it redraws) bring their own container and heading:
    they get the anchor and the background. Any other section gets the container and its heading.
    Background = the builder's "background" setting (alt / surface / light = grey, dark / primary = dark).
--}}
@props(['section'])
<section id="{{ $section->anchor }}" @class([
    'home-section',
    'home-section--alt' => in_array($section->style, ['alt', 'surface', 'light'], true),
    'home-section--dark' => in_array($section->style, ['dark', 'primary'], true),
    'home-section--' . str_replace('.', '-', (string) $section->key),
    'reveal' => theme_option('animations'),
])>
    @if (str_starts_with((string) $section->key, 'theme.') || str_starts_with((string) $section->key, 'core.') || view()->exists('theme::modules.'.str_replace('::', '.', (string) $section->view)))
        {{ $slot }}
    @else
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
    @endif
</section>
