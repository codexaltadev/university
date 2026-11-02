{{-- Header "minimal": one row — logo, the menu on the right, search; no utility row. Contract: $region, $config, $context --}}
<header x-data="siteHeader" @keydown.escape.window="close()" @click.outside="open = null; search = false"
        @class(['site-header', 'site-header--minimal', 'site-header--sticky' => $config['sticky'] ?? theme_option('sticky_header')])>
    <div class="container site-header__bar">
        @include('theme::partials.header-parts.brand')
        <div class="site-header__nav">
            <x-platform::menu location="primary" :depth="3" view="theme::partials.menus.mega" />
        </div>
        @include('theme::partials.header-parts.actions', ['search' => $config['show_search'] ?? true, 'cta' => false])
    </div>
    @if ($config['show_search'] ?? true)
        @include('theme::partials.header-parts.search')
    @endif
    @include('theme::partials.header-parts.drawer')
</header>
