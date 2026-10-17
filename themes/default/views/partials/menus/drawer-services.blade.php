{{-- Services tiles in the mobile menu (menu location "quick-links-icon") --}}
<div class="border-t border-gray-200/50 pt-4">
    <h3 class="text-sm font-semibold text-gray-500 mb-3">{{ __('theme::theme.services') }}</h3>
    <div class="grid grid-cols-3 gap-4">
        @foreach ($items as $item)
            @continue(! $item->url)
            <a href="{{ $item->url }}" @if ($item->target) target="_blank" rel="noopener" @endif class="flex flex-col items-center text-gray-700 hover:text-blue-600 hover:no-underline transition-all duration-300">
                <span class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-blue-50 transition-colors duration-300">
                    <x-theme::icon :name="$item->icon ?: 'external'" width="24" height="24" class="w-6 h-6" />
                </span>
                <span class="text-xs text-center mt-1">{{ $item->label }}</span>
            </a>
        @endforeach
    </div>
</div>
