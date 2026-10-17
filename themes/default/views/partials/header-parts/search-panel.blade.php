{{-- Search panel under the header (FR-SRCH-05): the kernel's instant search, scoped to this microsite --}}
<div id="site-search" class="search-panel" x-show="search" x-cloak x-transition.opacity @click.outside="search = false">
    <div class="container search-panel__inner">
        <livewire:platform.instant-search :scope="$context->isMicrosite() ? $context->key() : null" />
        <button type="button" class="icon-btn" @click="search = false">
            <x-theme::icon name="close" />
            <span class="sr-only">{{ __('theme::theme.close') }}</span>
        </button>
    </div>
</div>
