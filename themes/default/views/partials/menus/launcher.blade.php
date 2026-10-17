{{-- Services grid (menu location "quick-links-icon"): the grey square opens icon tiles. Item icon = an icon name of <x-theme::icon> --}}
<div class="relative hidden lg:block" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
    <button type="button" class="w-8 h-8 bg-gray-200 rounded flex items-center justify-center cursor-pointer hover:bg-gray-300" :class="{ 'text-blue-600': open }" @click="open = ! open" :aria-expanded="open">
        <x-theme::icon name="grid" />
        <span class="sr-only">{{ __('theme::theme.services') }}</span>
    </button>
    <div class="absolute right-0 mt-3 w-64 rounded-xl bg-white shadow-lg border border-gray-200 p-4 grid grid-cols-3 gap-4 z-50" x-show="open" x-transition x-cloak>
        @foreach ($items as $item)
            @continue(! $item->url)
            <a href="{{ $item->url }}" @if ($item->target) target="_blank" rel="noopener" @endif class="flex flex-col items-center text-gray-700 hover:text-blue-600 hover:no-underline transition-colors">
                <x-theme::icon :name="$item->icon ?: 'external'" width="24" height="24" class="w-6 h-6 mb-1" />
                <span class="text-xs text-center">{{ $item->label }}</span>
            </a>
        @endforeach
    </div>
</div>
