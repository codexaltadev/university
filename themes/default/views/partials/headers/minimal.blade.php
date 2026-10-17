{{-- Header "minimal": the glass bar with the logo and a one-level menu; search only when switched on --}}
<header x-data="siteHeader" @keydown.escape.window="close()" @class(['site-header site-header--minimal top-0 w-full z-50', 'fixed' => $config['sticky'] ?? false, 'absolute' => ! ($config['sticky'] ?? false), 'site-header--microsite' => $context->isMicrosite()])>
    {{-- the glass sits on its own layer: a backdrop-filter on <header> would trap the fixed menu and search window inside it --}}
    <div class="header-glass absolute inset-0 -z-10 bg-white/80 backdrop-blur-lg border-b border-gray-200/50 shadow-sm" aria-hidden="true"></div>
    <div class="header-row px-4 py-3">
        <div class="container mx-auto px-0 flex items-center justify-between">
            @include('theme::partials.header-parts.brand')
            <div class="hidden lg:flex items-center gap-6">
                <x-platform::menu location="primary" :depth="1" view="theme::partials.menus.inline" />
                @include('theme::partials.header-parts.actions', ['search' => $config['show_search'] ?? false])
            </div>
            @include('theme::partials.header-parts.mobile', ['search' => $config['show_search'] ?? false])
        </div>
    </div>
    @if ($config['show_search'] ?? false)
        @include('theme::partials.header-parts.search-panel')
    @endif
    @include('theme::partials.header-parts.offcanvas')
</header>
