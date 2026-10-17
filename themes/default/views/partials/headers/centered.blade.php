{{-- Header "centered": logo in the middle, the menu on its own row below (FR-HF-01, FR-SRCH-05) --}}
<header x-data="siteHeader" @keydown.escape.window="close()" @class(['site-header', 'site-header--centered', 'is-sticky' => $config['sticky'] ?? theme_option('sticky_header')])>
    <div class="container site-header__inner">
        <span class="site-header__spacer" aria-hidden="true"></span>
        @include('theme::partials.header-parts.brand')
        @include('theme::partials.header-parts.actions', ['search' => $config['show_search'] ?? true])
    </div>
    <div class="site-header__nav site-header__nav--row">
        <div class="container">
            <x-platform::menu location="primary" :depth="3" />
        </div>
    </div>
    @if ($config['show_search'] ?? true)
        @include('theme::partials.header-parts.search-panel')
    @endif
    @include('theme::partials.header-parts.offcanvas')
</header>
