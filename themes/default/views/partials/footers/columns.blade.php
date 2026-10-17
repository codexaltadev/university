{{--
    Footer "columns" = daffodilvarsity.edu.bd: blue band with two links, the "Start Your Journey"
    call-to-action on a campus photo (theme options), then Subscribe + social links and the
    link columns (the admin's columns, else the four footer menus), copyright (FR-HF-03).
--}}
<footer class="site-footer w-full text-white">
    @include('theme::partials.footer-parts.band')
    @include('theme::partials.footer-parts.cta')
    <div class="site-footer__links w-full">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 pb-16 pt-25">
            <div class="grid grid-cols-1 gap-10 md:grid-cols-12">
                <div class="md:col-span-4 lg:max-w-[400px]">
                    @moduleActive('newsletter')
                        <h3 class="text-xl font-bold text-white/90">{{ __('theme::theme.subscribe_us') }}</h3>
                        <div class="mt-3"><livewire:newsletter.subscribe source="footer" :compact="true" /></div>
                    @endmoduleActive
                    @if ($social = (array) settings('social.links', []))
                        <p class="mt-6 text-base text-white/70">{{ __('theme::theme.social_links') }}</p>
                        @include('theme::partials.social', ['links' => $social, 'class' => 'mt-3'])
                    @endif
                </div>
                <div class="md:col-span-8 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-8 gap-8 text-sm">
                    @forelse ($config['columns'] ?? [] as $column)
                        <div class="md:col-span-2">
                            @if ($heading = settings()->translate($column['heading'] ?? null))
                                <h4 class="site-footer__heading text-xl font-bold mb-3 text-white/95">{{ $heading }}</h4>
                            @endif
                            @foreach ($column['blocks'] ?? [] as $block)
                                <x-platform::footer-block :block="$block" />   {{-- the theme's view, else a module's block --}}
                            @endforeach
                        </div>
                    @empty
                        @foreach (['footer-1', 'footer-2', 'footer-3', 'footer-4'] as $location)
                            <div class="md:col-span-2">
                                <x-platform::menu :location="$location" :depth="1" view="theme::partials.menus.footer-list" />
                            </div>
                        @endforeach
                    @endforelse
                </div>
            </div>
            <div class="mt-5 border-t border-white/10 pt-6 text-base text-white/70 flex flex-col sm:flex-row items-center justify-center gap-2">
                {{ $region['copyright'] ?? 'Copyright © '.now()->year.' '.settings('general.site_name').'. All Rights Reserved.' }}
            </div>
        </div>
    </div>
    @include('theme::partials.footer-parts.back-to-top')
</footer>
