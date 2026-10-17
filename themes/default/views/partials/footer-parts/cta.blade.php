{{-- "Start Your Journey" on a campus photo (theme options footer_cta_*); hidden while the title is empty --}}
@if (filled(theme_option('footer_cta_title')))
    <div class="site-footer__cta relative">
        <div class="absolute inset-0">
            <img src="{{ theme_image('footer_cta_image', 'images/footer-cta.jpg') }}" alt="" loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover [object-position:50%_35%]">
            <div class="absolute top-[55%] inset-0 bg-gradient-to-t from-secondary via-secondary/50 to-transparent z-10"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-black/100 via-black/50 to-transparent"></div>
        </div>
        <div class="z-10 relative container mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-30">
            <div class="max-w-xl">
                {{-- "|" in the title = a line break, as on DIU ("Start Your Journey|with Us") --}}
                <h2 class="text-3xl sm:text-5xl leading-[1.1] font-bold text-white drop-shadow-md">
                    @foreach (explode('|', (string) theme_option('footer_cta_title')) as $line){{ trim($line) }}@if (! $loop->last)<br>@endif @endforeach
                </h2>
                @if (filled(theme_option('footer_cta_text')))
                    <p class="mt-3 text-base text-white/85 max-w-[42ch]">{{ theme_option('footer_cta_text') }}</p>
                @endif
                <div class="mt-6 flex flex-wrap items-center gap-6">
                    @if (filled(theme_option('header_cta_url')))
                        <a href="{{ theme_option('header_cta_url') }}" target="_blank" rel="noopener" class="rounded-md border border-primary bg-primary px-6 py-4 text-base font-semibold text-white shadow-sm hover:brightness-110 hover:no-underline">{{ filled(theme_option('header_cta_label')) ? theme_option('header_cta_label') : __('theme::theme.apply_now') }}</a>
                    @endif
                    @if (filled(theme_option('footer_cta_info_url')))
                        <a href="{{ theme_option('footer_cta_info_url') }}" class="rounded-md border px-6 py-4 text-base font-semibold text-white ring-1 ring-white/20 hover:bg-white/15 hover:no-underline inline-block">{{ theme_option('footer_cta_info_label') ?: __('theme::theme.request_info') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
