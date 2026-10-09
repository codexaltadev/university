<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-platform::head :title="$title ?? null" :description="$description ?? null" />
    @themeVite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body @class(['site', 'has-sticky-header' => theme_option('sticky_header')])>
    <a href="#main" class="skip-link">{{ __('theme::theme.skip_to_content') }}</a>

    <x-platform::slot name="topbar" />
    <x-platform::slot name="announcement" />
    <x-platform::slot name="header" />
    <x-platform::slot name="ticker" />
    <x-platform::slot name="breadcrumb" />

    <main id="main">
        {{ $slot }}
    </main>

    <x-platform::slot name="footer" />
    <x-platform::slot name="popup" />

    @livewireScripts
    {!! settings('code.footer_code') !!}
</body>
</html>
