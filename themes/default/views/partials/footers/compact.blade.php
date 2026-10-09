{{-- Footer "compact": one line with the footer menu and copyright --}}
<footer class="site-footer site-footer--compact">
    <div class="container site-footer__compact">
        <x-platform::menu location="footer-1" :depth="1" view="theme::partials.menus.inline" />
        <span>{{ $region['copyright'] ?? '© '.now()->year.' '.settings('general.site_name') }}</span>
    </div>
</footer>
