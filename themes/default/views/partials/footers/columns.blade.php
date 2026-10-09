{{-- Footer "columns" (FR-HF-03): the admin's columns and blocks; none → logo, contact and footer menus --}}
<footer class="site-footer">
    <div class="container site-footer__grid">
        @forelse ($config['columns'] ?? [] as $column)
            <div class="site-footer__col">
                @if ($heading = settings()->translate($column['heading'] ?? null))
                    <h2 class="site-footer__heading">{{ $heading }}</h2>
                @endif
                @foreach ($column['blocks'] ?? [] as $block)
                    @includeIf('theme::partials.footer-blocks.'.($block['type'] ?? 'none'), ['block' => $block])
                @endforeach
            </div>
        @empty
            <div class="site-footer__col">
                <x-platform::logo variant="footer" class="site-logo site-logo--footer" />
                @include('theme::partials.footer-blocks.contact', ['block' => []])
            </div>
            @foreach (['footer-1', 'footer-2', 'footer-3'] as $location)
                <div class="site-footer__col">
                    <x-platform::menu :location="$location" :depth="1" view="theme::partials.menus.list" />
                </div>
            @endforeach
        @endforelse
    </div>
    <div class="site-footer__bottom">
        <div class="container">{{ $region['copyright'] ?? '© '.now()->year.' '.settings('general.site_name') }}</div>
    </div>
</footer>
