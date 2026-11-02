{{-- Search, the header button (theme options; empty link = no button) and the menu button below 1024px --}}
<div class="site-header__actions">
    @if ($search ?? true)
        <button type="button" class="icon-btn" @click="openSearch()" :aria-expanded="search" aria-controls="site-search">
            <x-theme::icon name="search" /><span class="sr-only">{{ __('theme::theme.search') }}</span>
        </button>
    @endif
    @if (($cta ?? true) && filled($ctaUrl = theme_option('header_cta_url')) && filled($ctaLabel = theme_option('header_cta_label')))
        <a href="{{ $ctaUrl }}" class="btn btn--primary site-header__cta">{{ $ctaLabel }}</a>
    @endif
    <button type="button" class="icon-btn site-header__toggle" @click="openDrawer()" :aria-expanded="drawer" aria-controls="site-drawer">
        <x-theme::icon name="menu" /><span class="sr-only">{{ __('theme::theme.menu') }}</span>
    </button>
</div>
