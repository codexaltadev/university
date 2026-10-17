{{-- Header "centered": the classic glass bar with the logo in the middle and the menu on its own row --}}
<header x-data="siteHeader" @keydown.escape.window="close()" @class(['site-header site-header--centered top-0 w-full z-50', 'fixed' => $config['sticky'] ?? theme_option('sticky_header'), 'absolute' => ! ($config['sticky'] ?? theme_option('sticky_header')), 'site-header--microsite' => $context->isMicrosite()])>
    {{-- the glass sits on its own layer: a backdrop-filter on <header> would trap the fixed menu and search window inside it --}}
    <div class="absolute inset-0 -z-10 bg-white/80 backdrop-blur-lg border-b border-gray-200/50 shadow-sm" aria-hidden="true"></div>
    <div class="px-4 pt-3">
        <div class="container mx-auto px-0 flex items-center justify-between lg:justify-center">
            @include('theme::partials.header-parts.brand')
            @include('theme::partials.header-parts.mobile', ['search' => $config['show_search'] ?? true])
        </div>
        <div class="container mx-auto px-0 hidden lg:flex items-center justify-center space-x-8 relative">
            <x-platform::menu location="primary" :depth="3" />
            @include('theme::partials.header-parts.actions', ['search' => $config['show_search'] ?? true])
        </div>
    </div>
    @if ($config['show_search'] ?? true)
        @include('theme::partials.header-parts.search-panel')
    @endif
    @include('theme::partials.header-parts.offcanvas')
</header>
