{{-- Search, Apply button (theme options; a program's own application link on its pages, FR-PROG-13) and the Services grid (menu location "quick-links-icon") — DIU Navbar. Contract: $search (bool) --}}
<div class="flex items-center space-x-4">
    @if ($search)
        <button type="button" class="flex items-center gap-2 group cursor-pointer" @click="openSearch()" aria-controls="site-search" :aria-expanded="search">
            <x-theme::icon name="search" @class(['w-5 h-5 text-gray-600 hover:text-blue-600 transition-colors duration-300', 'ml-4' => $context->isMicrosite()]) />
            <span class="sr-only">{{ __('theme::theme.search') }}</span>
        </button>
    @endif
    @if (filled($applyUrl = $context->entity?->application_url ?: theme_option('header_cta_url')))
        <a href="{{ $applyUrl }}" target="_blank" rel="noopener" class="site-header__cta w-32 hover:no-underline">
            <span class="block bg-gradient-to-r from-primary to-secondary text-white px-6 py-2 rounded-lg font-medium shadow-lg shadow-blue-500/20 hover:shadow-xl hover:shadow-blue-500/30 hover:scale-105 transition-all duration-300">{{ filled(theme_option('header_cta_label')) ? theme_option('header_cta_label') : __('theme::theme.apply_now') }}</span>
        </a>
    @endif
    <x-platform::menu location="quick-links-icon" :depth="1" :main-site="true" view="theme::partials.menus.launcher" />
</div>
