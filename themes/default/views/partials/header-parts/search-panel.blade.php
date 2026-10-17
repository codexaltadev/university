{{-- Search window (DIU SearchModal): blurred page behind, white panel with the kernel's instant search (FR-SRCH-05), scoped to this microsite --}}
<div id="site-search" class="fixed inset-0 z-[80] flex items-center justify-center p-4 sm:p-6 lg:p-8" x-show="search" x-cloak role="dialog" aria-modal="true" aria-label="{{ __('theme::theme.search') }}">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-md" x-show="search" x-transition.opacity @click="search = false"></div>
    <div class="relative bg-white rounded-3xl shadow-2xl w-full h-full max-w-7xl max-h-[95vh] flex flex-col overflow-hidden" x-trap.noscroll="search" x-show="search" x-transition>
        <livewire:platform.instant-search :scope="$context->isMicrosite() ? $context->key() : null" />
    </div>
</div>
