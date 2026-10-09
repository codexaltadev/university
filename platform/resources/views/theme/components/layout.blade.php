{{-- <x-theme::layout :title="…"> — the theme's app or microsite layout, by context (Theme Guide §8.1) --}}
@props(['title' => null, 'description' => null])
@component(($context ?? null)?->isMicrosite() ? 'theme::layouts.microsite' : 'theme::layouts.app', ['title' => $title, 'description' => $description])
    {{ $slot }}
@endcomponent
