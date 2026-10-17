{{-- Round "back to top" button bottom right, shown once the page has scrolled (DIU) --}}
<div x-data="{ show: false }" x-init="show = scrollY > 600" @scroll.window.throttle.150ms="show = scrollY > 600">
    <a href="#main" x-show="show" x-transition.opacity x-cloak class="fixed bottom-28 right-6 z-40 w-12 h-12 rounded-full bg-secondary text-white flex items-center justify-center shadow-lg ring-2 ring-yellow-400 hover:bg-primary hover:no-underline"
       @click.prevent="window.scrollTo({ top: 0, behavior: 'smooth' })">
        <x-theme::icon name="chevron-up" />
        <span class="sr-only">{{ __('theme::theme.back_to_top') }}</span>
    </a>
</div>
