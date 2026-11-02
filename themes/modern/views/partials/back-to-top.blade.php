{{-- Back to top, once the page has scrolled --}}
<a href="#main" class="icon-btn back-to-top no-print" x-data="{ show: false }" @scroll.window.throttle.200ms="show = window.scrollY > 800" x-show="show" x-cloak x-transition.opacity>
    <x-theme::icon name="arrow-up" /><span class="sr-only">{{ __('theme::theme.back_to_top') }}</span>
</a>
