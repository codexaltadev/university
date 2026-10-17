{{-- Below 1024px: search and menu buttons (DIU Navbar). Contract: $search (bool) --}}
<div class="flex items-center lg:hidden space-x-4">
    @if ($search)
        <button type="button" class="cursor-pointer" @click="openSearch()" aria-controls="site-search" :aria-expanded="search">
            <x-theme::icon name="search" class="w-5 h-5 text-gray-600 hover:text-blue-600 transition-colors duration-300" />
            <span class="sr-only">{{ __('theme::theme.search') }}</span>
        </button>
    @endif
    <button type="button" class="site-header__burger lg:hidden ml-auto py-4 cursor-pointer" @click="drawer ? (drawer = false) : openDrawer()" aria-controls="site-drawer" :aria-expanded="drawer">
        <x-theme::icon name="menu" width="24" height="24" class="w-6 h-6" x-show="! drawer" />
        <x-theme::icon name="close" width="24" height="24" class="w-6 h-6" x-show="drawer" x-cloak />
        <span class="sr-only">{{ __('theme::theme.open_menu') }}</span>
    </button>
</div>
