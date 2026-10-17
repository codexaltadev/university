{{-- Header "classic": logo left, menu and actions right (FR-HF-01, FR-SRCH-05); below desktop the menu moves to the drawer --}}
<header x-data="siteHeader" @keydown.escape.window="close()" @class(['site-header', 'site-header--classic', 'is-sticky' => $config['sticky'] ?? theme_option('sticky_header')])>
    <div class="container site-header__inner">
        @include('theme::partials.header-parts.brand')
        <div class="site-header__nav">
            <x-platform::menu location="primary" :depth="3" />
        </div>
        @include('theme::partials.header-parts.actions', ['search' => $config['show_search'] ?? true])
    </div>
    @if ($config['show_search'] ?? true)
        @include('theme::partials.header-parts.search-panel')
    @endif
    @include('theme::partials.header-parts.offcanvas')
</header>
