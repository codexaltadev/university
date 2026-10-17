{{-- Utility links in the mobile menu ("Quick Links", two columns) --}}
<div class="border-t border-gray-200/50 pt-4">
    <h3 class="text-sm font-semibold text-gray-500 mb-3">{{ __('theme::theme.quick_links') }}</h3>
    <div class="grid grid-cols-2 gap-3">
        @foreach ($items as $item)
            @continue(! $item->url)
            <a href="{{ $item->url }}" @if ($item->target) target="_blank" rel="noopener" @endif class="text-sm text-gray-700 hover:text-blue-600 hover:no-underline transition-all duration-300">{{ $item->label }}</a>
        @endforeach
    </div>
</div>
