{{--
    Header "modern" (UI/UX Part B §12.4): utility links and languages in a thin row; logo (and the microsite's
    name), the primary menu with mega panels, search and the header button (theme options). Below 1024px:
    search and a menu button; the menu opens as a drawer from the side. Contract: $region, $config, $context
--}}
@include('theme::partials.header-parts.utility')
<header x-data="siteHeader" @keydown.escape.window="close()" @click.outside="open = null; search = false"
        @class(['site-header', 'site-header--modern', 'site-header--sticky' => $config['sticky'] ?? theme_option('sticky_header')])>
    <div class="container site-header__bar">
        @include('theme::partials.header-parts.brand')
        <div class="site-header__nav">
            <x-platform::menu location="primary" :depth="3" view="theme::partials.menus.mega" />
        </div>
        @include('theme::partials.header-parts.actions', ['search' => $config['show_search'] ?? true])
    </div>
    @if ($config['show_search'] ?? true)
        @include('theme::partials.header-parts.search')
    @endif
    @include('theme::partials.header-parts.drawer')
</header>
