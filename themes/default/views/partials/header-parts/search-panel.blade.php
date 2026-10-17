{{-- Search window (DIU): blurred page behind, white panel with the kernel's instant search (FR-SRCH-05), scoped to this microsite --}}
<div id="site-search" class="fixed inset-0 z-[80] flex items-start justify-center p-4 md:p-8 lg:px-20" x-show="search" x-cloak role="dialog" aria-modal="true" aria-label="{{ __('theme::theme.search') }}">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" x-show="search" x-transition.opacity @click="search = false"></div>
    <div class="relative w-full max-w-7xl h-full max-h-[calc(100vh-4rem)] bg-white rounded-3xl shadow-2xl flex flex-col overflow-hidden" x-trap.noscroll="search" x-show="search" x-transition>
        <div class="flex items-center justify-between px-6 pt-6 mb-4">
            <h2 class="text-xl font-semibold text-gray-900">{{ __('theme::theme.search') }}</h2>
            <button type="button" class="text-gray-500 hover:text-gray-900 cursor-pointer" @click="search = false">
                <x-theme::icon name="close" />
                <span class="sr-only">{{ __('theme::theme.close') }}</span>
            </button>
        </div>
        <livewire:platform.instant-search :scope="$context->isMicrosite() ? $context->key() : null" />
    </div>
</div>
