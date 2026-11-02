{{-- Drawer for phones and tablets (UI/UX Part B §4.3): the primary menu as an accordion, utility links, languages --}}
<div id="site-drawer" class="drawer" x-show="drawer" x-cloak role="dialog" aria-modal="true" aria-label="{{ __('theme::theme.menu') }}">
    <div class="drawer__backdrop" @click="close()" x-show="drawer" x-transition.opacity></div>
    <div class="drawer__panel" x-show="drawer" x-transition:enter="transition duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
        <div class="drawer__head">
            <x-platform::logo variant="light" class="h-9 w-auto" />
            <button type="button" class="icon-btn" @click="close()"><x-theme::icon name="close" /><span class="sr-only">{{ __('theme::theme.close') }}</span></button>
        </div>
        <div class="drawer__body">
            <x-platform::menu location="primary" :depth="3" view="theme::partials.menus.drawer" />
            <x-platform::menu location="topbar" :depth="1" view="theme::partials.menus.drawer" />
            @if (filled($ctaUrl = theme_option('header_cta_url')) && filled($ctaLabel = theme_option('header_cta_label')))
                <a href="{{ $ctaUrl }}" class="btn btn--primary">{{ $ctaLabel }}</a>
            @endif
            <x-platform::languages class="languages" />
        </div>
    </div>
</div>
