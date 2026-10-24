{{-- Mobile menu (DIU Navbar drawer): panel from the right — the primary menu as accordions, Apply button, Quick Links (utility links), Services tiles. Focus stays inside; Esc closes --}}
<div id="site-drawer" class="lg:hidden" x-show="drawer" x-cloak role="dialog" aria-modal="true" aria-label="{{ __('theme::theme.menu') }}">
    <div class="fixed top-0 right-0 w-full h-screen bg-black/50 backdrop-blur-md z-40" x-show="drawer" x-transition.opacity @click="drawer = false"></div>
    <div class="drawer__panel fixed top-0 right-0 w-80 max-w-[85vw] h-screen bg-white shadow-2xl z-50 flex flex-col rounded-l-3xl" x-trap.noscroll="drawer">
        <div class="flex items-center justify-between px-4 py-4 border-b border-gray-200/50">
            <span class="text-lg font-semibold text-gray-800">{{ __('theme::theme.menu') }}</span>
            <button type="button" class="cursor-pointer hover:rotate-90 transition-transform duration-200" @click="drawer = false">
                <x-theme::icon name="close" width="24" height="24" class="w-6 h-6 text-gray-600" />
                <span class="sr-only">{{ __('theme::theme.close_menu') }}</span>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto px-4 py-6 space-y-8">
            <div class="flex flex-col">
                <x-platform::menu location="primary" :depth="3" view="theme::partials.menus.offcanvas" />
                @if (filled($applyUrl = $context->entity?->application_url ?: theme_option('header_cta_url')))   {{-- a program's own application link on its pages --}}
                    <a href="{{ $applyUrl }}" target="_blank" rel="noopener" class="w-full bg-gradient-to-r from-primary to-secondary text-white px-6 py-3 rounded-lg font-medium hover:opacity-90 hover:no-underline transition-all duration-300 shadow-lg hover:shadow-xl mt-6">{{ filled(theme_option('header_cta_label')) ? theme_option('header_cta_label') : __('theme::theme.apply_now') }}</a>
                @endif
            </div>
            <x-platform::menu location="topbar" :depth="1" :main-site="true" view="theme::partials.menus.drawer-links" />
            <x-platform::menu location="quick-links-icon" :depth="1" :main-site="true" view="theme::partials.menus.drawer-services" />
            <x-platform::languages class="language-switcher--pill" />
        </div>
    </div>
</div>
