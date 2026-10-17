{{-- Footer "columns" (FR-HF-03): the admin's columns and blocks; none → logo, contact, social and the footer menus --}}
<footer class="site-footer">
    <div class="container site-footer__grid">
        @forelse ($config['columns'] ?? [] as $column)
            <div class="site-footer__col">
                @if ($heading = settings()->translate($column['heading'] ?? null))
                    <h2 class="site-footer__heading">{{ $heading }}</h2>
                @endif
                @foreach ($column['blocks'] ?? [] as $block)
                    <x-platform::footer-block :block="$block" />   {{-- the theme's view, else a module's block --}}
                @endforeach
            </div>
        @empty
            <div class="site-footer__col site-footer__col--brand">
                @include('theme::partials.footer-blocks.logo')
                @include('theme::partials.footer-blocks.contact', ['block' => []])
                @include('theme::partials.footer-blocks.social', ['block' => []])
            </div>
            @foreach (['footer-1', 'footer-2', 'footer-3'] as $location)
                <div class="site-footer__col">
                    <x-platform::menu :location="$location" :depth="1" view="theme::partials.menus.list" />
                </div>
            @endforeach
        @endforelse
    </div>
    <div class="site-footer__bottom">
        <div class="container site-footer__bottom-inner">
            <span>{{ $region['copyright'] ?? '© '.now()->year.' '.settings('general.site_name') }}</span>
            <x-platform::menu location="footer-4" :depth="1" view="theme::partials.menus.inline" />
            <a href="#main" class="site-footer__top"><x-theme::icon name="arrow-up" width="16" height="16" />{{ __('theme::theme.back_to_top') }}</a>
        </div>
    </div>
</footer>
