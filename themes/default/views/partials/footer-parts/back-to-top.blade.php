{{-- Back to top (DIU ScrollToTopButton): round button inside a yellow ring that fills as the page scrolls; shown after 300px --}}
<div x-data="{ show: false, progress: 0 }"
     x-init="const update = () => { const h = document.documentElement.scrollHeight - innerHeight; show = scrollY > 300; progress = h > 0 ? Math.min(100, scrollY / h * 100) : 0 }; update(); addEventListener('scroll', update, { passive: true })">
    <div class="fixed bottom-26 right-6 z-50" x-show="show" x-transition.opacity x-cloak>
        <div class="relative">
            <svg class="w-14 h-14 -rotate-90" viewBox="0 0 56 56" aria-hidden="true">
                <circle cx="28" cy="28" r="24" stroke-width="4" fill="none" class="stroke-yellow-400/20" />
                <circle cx="28" cy="28" r="24" stroke-width="4" fill="none" stroke-linecap="round" class="stroke-yellow-400 transition-all duration-150 ease-out"
                        stroke-dasharray="150.8" :stroke-dashoffset="150.8 * (1 - progress / 100)" />
            </svg>
            <button type="button" class="absolute inset-0 m-1 bg-primary hover:bg-secondary text-white rounded-full shadow-lg transition-all duration-300 ease-in-out hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-600/50 active:scale-95 cursor-pointer"
                    @click="window.scrollTo({ top: 0, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' })" title="{{ __('theme::theme.back_to_top') }}">
                <x-theme::icon name="chevron-up" class="h-5 w-5 mx-auto" />
                <span class="sr-only">{{ __('theme::theme.back_to_top') }}</span>
            </button>
        </div>
    </div>
</div>
