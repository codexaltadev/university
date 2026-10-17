{{-- Utility links (menu location "topbar"): small grey links with a growing underline; the language switcher at the end when the website has more than one language --}}
<div class="header-utility overflow-hidden transition-all duration-300">
    <div class="flex justify-end border-b border-gray-200/50 pb-2 mb-3">
        <div class="flex space-x-4 text-sm text-gray-600">
            @foreach ($items as $item)
                @continue(! $item->url)
                <a href="{{ $item->url }}" @if ($item->target) target="_blank" rel="noopener" @endif class="hover:text-blue-600 hover:no-underline transition-all duration-300 ease-out relative group">{{ $item->label }}<span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-blue-600 transition-all duration-300 group-hover:w-full"></span></a>
            @endforeach
        </div>
        <x-platform::languages class="language-switcher--inline ml-4 text-sm" />
    </div>
</div>
