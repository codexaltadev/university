{{-- Footer "compact": the dark band only — footer menu, social links and copyright --}}
<footer class="site-footer site-footer--compact w-full text-white">
    <div class="site-footer__links w-full">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10 flex flex-col md:flex-row items-center justify-between gap-6">
            <x-platform::menu location="footer-1" :depth="1" view="theme::partials.menus.inline" />
            @if ($social = (array) settings('social.links', []))
                @include('theme::partials.social', ['links' => $social, 'class' => ''])
            @endif
        </div>
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 pb-8">
            <div class="border-t border-white/10 pt-6 text-base text-white/70 text-center">{{ $region['copyright'] ?? 'Copyright © '.now()->year.' '.settings('general.site_name').'. All Rights Reserved.' }}</div>
        </div>
    </div>
    @include('theme::partials.footer-parts.back-to-top')
</footer>
