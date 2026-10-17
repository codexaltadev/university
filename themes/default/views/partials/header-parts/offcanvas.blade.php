{{-- Mobile off-canvas menu (UI/UX Part B §4.3): the primary menu as an accordion, languages, CTA, contact. Focus stays inside; Esc closes --}}
<div id="site-drawer" class="drawer" x-show="drawer" x-cloak role="dialog" aria-modal="true" aria-label="{{ __('theme::theme.primary_nav') }}">
    <div class="drawer__backdrop" @click="drawer = false"></div>
    <div class="drawer__panel" x-trap.noscroll="drawer">
        <div class="drawer__head">
            <x-platform::languages class="language-switcher--pill" />
            <button type="button" class="icon-btn" @click="drawer = false">
                <x-theme::icon name="close" />
                <span class="sr-only">{{ __('theme::theme.close_menu') }}</span>
            </button>
        </div>
        <x-platform::menu location="primary" :depth="3" view="theme::partials.menus.offcanvas" />
        @if (filled(theme_option('header_cta_url')))
            <a href="{{ theme_option('header_cta_url') }}" class="btn btn--primary drawer__cta">{{ filled(theme_option('header_cta_label')) ? theme_option('header_cta_label') : __('theme::theme.apply_now') }}</a>
        @endif
        <div class="drawer__contact">
            @if (settings('contact.phone'))
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', settings('contact.phone')) }}"><x-theme::icon name="phone" width="16" height="16" />{{ settings('contact.phone') }}</a>
            @endif
            @if (settings('contact.email'))
                <a href="mailto:{{ settings('contact.email') }}"><x-theme::icon name="mail" width="16" height="16" />{{ settings('contact.email') }}</a>
            @endif
            @if ($social = (array) settings('social.links', []))
                @include('theme::partials.social', ['links' => $social, 'class' => 'drawer__social'])
            @endif
        </div>
    </div>
</div>
