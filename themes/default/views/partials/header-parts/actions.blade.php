{{-- Header actions: search toggle, the call-to-action button (theme options), menu button below desktop. Contract: $search (bool) --}}
<div class="site-header__actions">
    @if ($search)
        <button type="button" class="icon-btn" @click="toggleSearch()" :aria-expanded="search" aria-controls="site-search">
            <x-theme::icon name="search" />
            <span class="sr-only">{{ __('theme::theme.search') }}</span>
        </button>
    @endif
    @if (filled(theme_option('header_cta_url')))
        <a href="{{ theme_option('header_cta_url') }}" class="btn btn--primary site-header__cta">{{ filled(theme_option('header_cta_label')) ? theme_option('header_cta_label') : __('theme::theme.apply_now') }}</a>
    @endif
    <button type="button" class="icon-btn site-header__burger" @click="openDrawer()" :aria-expanded="drawer" aria-controls="site-drawer">
        <x-theme::icon name="menu" />
        <span class="sr-only">{{ __('theme::theme.open_menu') }}</span>
    </button>
</div>
