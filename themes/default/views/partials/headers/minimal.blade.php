{{-- Header "minimal": logo and a one-level menu; search only when switched on --}}
<header x-data="siteHeader" @keydown.escape.window="close()" @class(['site-header', 'site-header--minimal', 'is-sticky' => $config['sticky'] ?? false])>
    <div class="container site-header__inner">
        @include('theme::partials.header-parts.brand')
        <div class="site-header__nav">
            <x-platform::menu location="primary" :depth="1" view="theme::partials.menus.inline" />
        </div>
        @include('theme::partials.header-parts.actions', ['search' => $config['show_search'] ?? false])
    </div>
    @if ($config['show_search'] ?? false)
        @include('theme::partials.header-parts.search-panel')
    @endif
    @include('theme::partials.header-parts.offcanvas')
</header>
