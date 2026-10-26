{{--
    A page without the header, footer and slots — the theme's styles only (maintenance; a page that must not depend on menus).
    <x-theme::bare :title="…">…</x-theme::bare>
--}}
@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-platform::head :title="$title" />
    @themeVite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site">
    <main id="main">{{ $slot }}</main>
</body>
</html>
