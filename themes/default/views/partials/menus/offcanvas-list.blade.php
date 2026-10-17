{{-- One item of the mobile menu (DIU MobileDepartmentMenuItem), nested: a link, or an accordion of its children. Contract: $item, $depth --}}
<div @class(['border-b border-gray-200' => $depth === 0]) @if ($item->children->isNotEmpty()) x-data="{ open: false }" @endif>
    @if ($item->children->isNotEmpty())
        <button type="button" @click="open = ! open" :aria-expanded="open"
                @class(['w-full flex items-center justify-between text-left transition-all duration-300 cursor-pointer',
                        'py-3 text-gray-800 hover:text-blue-600 font-medium text-base' => $depth === 0,
                        'py-2 text-gray-800 hover:text-primary font-medium text-sm' => $depth > 0])>
            <span>{{ $item->label }}</span>
            <span class="shrink-0 ml-2 transition-transform duration-300" :class="{ 'rotate-180': open }">
                <x-theme::icon name="chevron-down" @class(['w-5 h-5' => $depth === 0, 'w-4 h-4 text-gray-500' => $depth > 0]) />
            </span>
        </button>
        <div x-show="open" x-collapse x-cloak>
            <div @class(['pl-4 pb-4 space-y-1.5' => $depth === 0, 'pl-3 pb-2 border-l-2 border-gray-100 ml-1 space-y-1 mt-1' => $depth > 0])>
                <div class="space-y-1">
                    @foreach ($item->children as $child)
                        @include('theme::partials.menus.offcanvas-list', ['item' => $child, 'depth' => $depth + 1])
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <a href="{{ $item->url ?? '#' }}" @if ($item->target) target="_blank" rel="noopener noreferrer" @endif @if ($item->isActive) aria-current="page" @endif
           @class(['w-full flex items-center justify-between transition-all duration-200 hover:no-underline',
                   'py-3 text-gray-800 hover:text-blue-600 font-medium text-base' => $depth === 0,
                   'py-1.5 text-sm text-gray-700 hover:text-primary' => $depth > 0])>{{ $item->label }}</a>
    @endif
</div>
