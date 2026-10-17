{{-- Below 1024px: search and menu buttons (DIU). Contract: $search (bool) --}}
<div class="flex items-center lg:hidden space-x-4">
    @if ($search)
        <button type="button" class="text-gray-700 cursor-pointer" @click="openSearch()" aria-controls="site-search" :aria-expanded="search">
            <x-theme::icon name="search" width="24" height="24" />
            <span class="sr-only">{{ __('theme::theme.search') }}</span>
        </button>
    @endif
    <button type="button" class="site-header__burger py-4 text-gray-900 cursor-pointer" @click="openDrawer()" aria-controls="site-drawer" :aria-expanded="drawer">
        <x-theme::icon name="menu" width="24" height="24" />
        <span class="sr-only">{{ __('theme::theme.open_menu') }}</span>
    </button>
</div>
