{{--
    <x-platform::languages class="…" /> — the language switcher (UI/UX Part B §6, BR-LANG):
    this page in every website language, with hreflang and lang. Nothing while the
    website has one language. A theme styles .language-switcher or passes its own class.
--}}
@php($links = app(\Platform\Seo\Seo::class)->languageLinks())
@if (count($links) > 1)
    <ul {{ $attributes->merge(['class' => 'language-switcher']) }} aria-label="{{ __('platform::localization.switcher') }}">
        @foreach ($links as $link)
            <li>
                <a href="{{ $link['url'] }}" hreflang="{{ $link['code'] }}" lang="{{ $link['code'] }}" @if ($link['current']) aria-current="true" @endif>{{ $link['label'] }}</a>
            </li>
        @endforeach
    </ul>
@endif
