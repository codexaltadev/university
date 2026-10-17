{{-- Mobile menu (DIU): panel from the right — the primary menu as accordions, Apply button, utility links, Services tiles. Focus stays inside; Esc closes --}}
<div id="site-drawer" class="fixed inset-0 z-[70] lg:hidden" x-show="drawer" x-cloak role="dialog" aria-modal="true" aria-label="{{ __('theme::theme.menu') }}">
    <div class="absolute inset-0 bg-black/40" x-show="drawer" x-transition.opacity @click="drawer = false"></div>
    <div class="drawer__panel absolute right-0 top-0 h-full w-80 max-w-[85vw] bg-white rounded-l-3xl shadow-2xl overflow-y-auto" x-trap.noscroll="drawer">
        <div class="flex items-center justify-between px-4 py-4 border-b border-gray-200/50">
            <h2 class="text-xl font-medium text-gray-900">{{ __('theme::theme.menu') }}</h2>
            <button type="button" class="text-gray-700 cursor-pointer" @click="drawer = false">
                <x-theme::icon name="close" width="24" height="24" />
                <span class="sr-only">{{ __('theme::theme.close_menu') }}</span>
            </button>
        </div>
        <div class="px-4 py-6 space-y-6">
            <div>
                <x-platform::menu location="primary" :depth="3" view="theme::partials.menus.offcanvas" />
                @if (filled(theme_option('header_cta_url')))
                    <a href="{{ theme_option('header_cta_url') }}" target="_blank" rel="noopener" class="block w-full bg-gradient-to-r from-primary to-secondary text-white px-6 py-3 rounded-lg font-medium hover:opacity-90 hover:no-underline transition-all duration-300 shadow-lg hover:shadow-xl mt-6">{{ filled(theme_option('header_cta_label')) ? theme_option('header_cta_label') : __('theme::theme.apply_now') }}</a>
                @endif
            </div>
            <x-platform::menu location="topbar" :depth="1" view="theme::partials.menus.drawer-links" />
            <x-platform::menu location="quick-links-icon" :depth="1" view="theme::partials.menus.drawer-services" />
            <x-platform::languages class="language-switcher--pill" />
        </div>
    </div>
</div>
