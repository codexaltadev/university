{{--
    <x-platform::menu location="primary" :depth="3" view="theme::partials.menus.mega" />
    The menu that applies at a location (Theme Guide §7.2). Without a view: the theme's
    view for the menu's style (dropdown / multilevel / mega), else its dropdown.
    :main-site="true" — always the main site's menu, also on a microsite (site-wide links
    such as a services grid).
--}}
@props(['location', 'depth' => 3, 'view' => null, 'mainSite' => false])
@php
    $context = $mainSite ? \Platform\Context\SiteContext::site() : ($context ?? \Platform\Context\SiteContext::site());
    $menu = app(\Platform\Theme\SiteData::class)->get('menu', $location, $context) ?? ['style' => null, 'items' => []];
    $items = \Platform\Theme\MenuItems::from($menu['items'], (int) $depth);
    $style = $menu['style'] ?? 'dropdown';
    $view ??= collect(["theme::partials.menus.{$style}", 'theme::partials.menus.dropdown'])->first(fn (string $v): bool => view()->exists($v));
@endphp
@if ($items->isNotEmpty() && $view)
    @include($view, ['items' => $items, 'style' => $style, 'location' => $location, 'attributes' => $attributes])
@endif
