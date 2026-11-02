{{-- Footer "simple": one row — the footer-1 menu, social links, copyright. Contract: $region, $config, $context --}}
<footer class="site-footer">
    <div class="container site-footer__bottom" style="border-top: 0">
        <x-platform::menu location="footer-1" :depth="1" view="theme::partials.menus.inline" />
        @include('theme::partials.social', ['links' => (array) settings('social.links', []), 'class' => 'mt-0'])
        <span>{{ $region['copyright'] ?? __('theme::theme.copyright', ['year' => now()->year, 'name' => settings('general.site_name')]) }}</span>
    </div>
</footer>
