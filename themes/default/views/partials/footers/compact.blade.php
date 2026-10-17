{{-- Footer "compact": logo, the footer menu, social icons and copyright in one band --}}
<footer class="site-footer site-footer--compact">
    <div class="container site-footer__compact">
        @include('theme::partials.footer-blocks.logo')
        <x-platform::menu location="footer-1" :depth="1" view="theme::partials.menus.inline" />
        @include('theme::partials.footer-blocks.social', ['block' => []])
    </div>
    <div class="site-footer__bottom">
        <div class="container site-footer__bottom-inner">
            <span>{{ $region['copyright'] ?? '© '.now()->year.' '.settings('general.site_name') }}</span>
        </div>
    </div>
</footer>
