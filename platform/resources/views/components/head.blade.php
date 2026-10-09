{{-- <x-platform::head :title="…" :description="…" /> — title, meta, favicon, fonts and the design-token CSS (SAD Part B §8.4) --}}
@props(['title' => null, 'description' => null])
@php
    $themes = app(\Platform\Theme\Themes::class);
    $tokens = app(\Platform\Theme\DesignTokens::class);
    $theme = $themes->current();
    $siteName = settings('general.site_name');
    $description ??= settings('general.tagline');
    $favicon = settings()->media('branding.favicon');
    $fonts = $tokens->fontsUrl($theme);
    $analytics = settings('code.google_analytics_id');
@endphp
<title>{{ $title ? $title.' | '.$siteName : $siteName }}</title>
@if ($description)
    <meta name="description" content="{{ $description }}">
@endif
@if ($themes->previewing())
    <meta name="robots" content="noindex, nofollow">
@endif
@if ($favicon)
    <link rel="icon" href="{{ $favicon->url() }}">
@endif
@if ($fonts)
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ $fonts }}">
@endif
<link rel="stylesheet" href="{{ $tokens->stylesheetUrl($theme) }}">
@if ($analytics)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $analytics }}"></script>
    <script>window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', @js($analytics));</script>
@endif
{!! settings('code.header_code') !!}
