{{--
    <x-platform::logo variant="light|dark|footer" class="…" /> (FR-THEME-06): the entity's
    own logo when a module provides one, else the website branding, else the site name.
--}}
@props(['variant' => 'light'])
@php
    $context ??= \Platform\Context\SiteContext::site();
    $resolver = app(\Platform\Context\FallbackResolver::class);
    $logo = $resolver->has('logo') ? $resolver->resolve('logo', $variant, $context) : null;

    if (! $logo instanceof \Platform\Media\MediaItem) {
        $logo = settings()->media("branding.logo_{$variant}") ?? settings()->media('branding.logo_light');
    }

    $siteName = settings('general.site_name');
@endphp
@if ($logo)
    <img src="{{ $logo->url() }}" alt="{{ $siteName }}" {{ $attributes }}>
@else
    <span {{ $attributes->merge(['class' => 'site-name']) }}>{{ $siteName }}</span>
@endif
