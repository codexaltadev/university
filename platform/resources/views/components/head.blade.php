{{--
    <x-platform::head :title="…" :description="…" /> — title, SEO tags (C-12), favicon, fonts and the
    design-token CSS (SAD Part B §8.4). The record of the page comes from Platform\Seo\Seo.
--}}
@props(['title' => null, 'description' => null])
@php
    $themes = app(\Platform\Theme\Themes::class);
    $tokens = app(\Platform\Theme\DesignTokens::class);
    $theme = $themes->current();
    $seo = app(\Platform\Seo\Seo::class)->tags($title, $description);
    $favicon = settings()->media('branding.favicon');
    $fonts = $tokens->fontsUrl($theme);
    $analytics = settings('code.google_analytics_id');
@endphp
<title>{{ $seo['title'] }}</title>
@if ($seo['description'])
    <meta name="description" content="{{ $seo['description'] }}">
@endif
@if ($seo['robots'])
    <meta name="robots" content="{{ $seo['robots'] }}">
@endif
<link rel="canonical" href="{{ $seo['canonical'] }}">
@foreach ($seo['alternates'] as $hreflang => $href)
    <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}">
@endforeach
<meta property="og:type" content="{{ $seo['type'] }}">
<meta property="og:title" content="{{ $seo['og_title'] }}">
<meta property="og:site_name" content="{{ $seo['site_name'] }}">
<meta property="og:url" content="{{ $seo['canonical'] }}">
<meta property="og:locale" content="{{ $seo['locale'] }}">
@if ($seo['description'])
    <meta property="og:description" content="{{ $seo['description'] }}">
@endif
@if ($seo['image'])
    <meta property="og:image" content="{{ $seo['image'] }}">
    <meta name="twitter:card" content="summary_large_image">
@endif
@if ($seo['google'])
    <meta name="google-site-verification" content="{{ $seo['google'] }}">
@endif
@if ($seo['bing'])
    <meta name="msvalidate.01" content="{{ $seo['bing'] }}">
@endif
@foreach ($seo['jsonld'] as $block)
    <script type="application/ld+json">{!! json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endforeach
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
