{{-- Services grid (DIU QuickMenu; menu location "quick-links-icon"): opens on hover (and on click / keyboard). Item icon = an icon name of <x-theme::icon> --}}
<div class="site-header__launcher relative hidden lg:block" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" @click.outside="open = false" @keydown.escape="open = false">
    <button type="button" class="w-8 h-8 bg-gray-200 rounded flex items-center justify-center cursor-pointer hover:bg-gray-300" @click="open = ! open" :aria-expanded="open">
        <x-theme::icon name="grid" class="w-5 h-5 text-gray-600 hover:text-blue-600" />
        <span class="sr-only">{{ __('theme::theme.services') }}</span>
    </button>
    <div class="absolute right-0 mt-3 w-64 rounded-xl bg-white shadow-lg border border-gray-200 p-4 grid grid-cols-3 gap-4 z-50" x-show="open" x-transition x-cloak>
        @foreach ($items as $item)
            @continue(! $item->url)
            <a href="{{ $item->url }}" target="_blank" rel="noopener" class="flex flex-col items-center text-gray-700 hover:text-blue-600 hover:no-underline transition-colors">
                <x-theme::icon :name="$item->icon ?: 'external'" width="24" height="24" class="w-6 h-6 mb-1" />
                <span class="text-xs text-center">{{ $item->label }}</span>
            </a>
        @endforeach
    </div>
</div>
