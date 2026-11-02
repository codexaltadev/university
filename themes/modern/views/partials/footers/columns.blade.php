{{--
    Footer "columns" (FR-HF-03, UI/UX Part B §12.4): logo, the footer text (theme option, else the tagline) and
    social links; then the admin's columns (else the footer menus); copyright. Contract: $region, $config, $context
--}}
<footer class="site-footer">
    <div class="container site-footer__main">
        <div>
            <a href="{{ url('/') }}" class="site-footer__brand inline-block"><x-platform::logo variant="footer" /></a>
            @if ($text = theme_option('footer_tagline') ?: settings('general.tagline'))
                <p class="site-footer__text">{{ $text }}</p>
            @endif
            @include('theme::partials.social', ['links' => (array) settings('social.links', [])])
        </div>
        @forelse ($config['columns'] ?? [] as $column)
            <div>
                @if ($heading = settings()->translate($column['heading'] ?? null))
                    <h2 class="site-footer__heading">{{ $heading }}</h2>
                @endif
                <div class="grid gap-4">
                    @foreach ($column['blocks'] ?? [] as $block)
                        <x-platform::footer-block :block="$block" />
                    @endforeach
                </div>
            </div>
        @empty
            @foreach (['footer-1', 'footer-2', 'footer-3'] as $location)
                <div>
                    <x-platform::menu :location="$location" :depth="1" view="theme::partials.menus.footer-list" />
                </div>
            @endforeach
        @endforelse
    </div>
    <div class="container site-footer__bottom">
        <span>{{ $region['copyright'] ?? __('theme::theme.copyright', ['year' => now()->year, 'name' => settings('general.site_name')]) }}</span>
        <x-platform::languages class="languages" />
    </div>
    @include('theme::partials.back-to-top')
</footer>
