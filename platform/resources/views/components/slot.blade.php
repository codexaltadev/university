{{--
    <x-platform::slot name="header" /> — one layout slot (Theme Guide §5). Top bar, header and
    footer use the region that applies here (own → parent … → main site) and its variant;
    a variant the theme does not have falls back to the theme's first one. Other slots
    include theme::partials.{name} when the theme has it.
--}}
@props(['name'])
@php
    $context ??= \Platform\Context\SiteContext::site();
    $themes = app(\Platform\Theme\Themes::class);
    $region = in_array($name, ['topbar', 'header', 'footer'], true)
        ? app(\Platform\Theme\SiteData::class)->get('region', $name, $context)
        : null;
    $variants = $themes->manifest()->variants($name);
    $variant = in_array($region['variant'] ?? null, $variants, true) ? $region['variant'] : ($variants[0] ?? null);
    $candidates = match ($name) {
        'header', 'footer' => array_filter([$variant ? "theme::partials.{$name}s.{$variant}" : null, "theme::partials.{$name}"]),
        default => ["theme::partials.{$name}"],
    };
    $view = collect($candidates)->first(fn (string $v): bool => view()->exists($v));
@endphp
@if ($name === 'topbar' && $themes->previewing())
    @include('theme::partials.preview-bar', ['theme' => $themes->current()])
@endif
@if ($view)
    @include($view, ['region' => $region, 'config' => (array) ($region['config'] ?? []), 'variant' => $variant, 'context' => $context])
@endif
