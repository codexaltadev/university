{{--
    Header "classic" = daffodilvarsity.edu.bd: glass bar over the page; logo (and the microsite's
    name); utility links above the primary menu on the main site; search, Apply, Services grid.
    Below 1024px: search and menu buttons, the menu opens from the right (FR-HF-01, FR-SRCH-05).
--}}
<header x-data="siteHeader" @keydown.escape.window="close()" @class(['site-header site-header--classic top-0 w-full z-50', 'fixed' => $config['sticky'] ?? theme_option('sticky_header'), 'absolute' => ! ($config['sticky'] ?? theme_option('sticky_header')), 'site-header--microsite' => $context->isMicrosite()])>
    {{-- the glass sits on its own layer: a backdrop-filter on <header> would trap the fixed menu and search window inside it --}}
    <div class="header-glass absolute inset-0 -z-10 bg-white/80 backdrop-blur-lg border-b border-gray-200/50 shadow-sm" aria-hidden="true"></div>
    <div class="header-row px-4 pt-3">
        <div class="container mx-auto px-0 flex items-center justify-between">
            @include('theme::partials.header-parts.brand')
            <div class="hidden lg:flex flex-col flex-1 ml-6 xl:ml-8">
                @unless ($context->isMicrosite())
                    @include('theme::partials.header-parts.utility')
                @endunless
                <div class="flex items-center justify-end space-x-4 xl:space-x-8 relative">
                    <x-platform::menu location="primary" :depth="3" />
                    @include('theme::partials.header-parts.actions', ['search' => $config['show_search'] ?? true])
                </div>
            </div>
            @include('theme::partials.header-parts.mobile', ['search' => $config['show_search'] ?? true])
        </div>
    </div>
    @if ($config['show_search'] ?? true)
        @include('theme::partials.header-parts.search-panel')
    @endif
    @include('theme::partials.header-parts.offcanvas')
</header>
