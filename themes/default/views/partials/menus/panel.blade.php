{{-- One dropdown panel (DIU department menu): the item's name over a rule, then its links; a link with children opens beside. Contract: $item, $index --}}
<div class="nav__panel absolute top-full mt-0 left-0 bg-white shadow-2xl border border-gray-100 rounded-lg z-[100] min-w-80 max-w-md" x-show="open === {{ $index }}" x-transition.opacity x-cloak>
    <div class="px-8 py-8">
        <div class="pb-3 border-b border-gray-200 mb-4">
            <h3 class="text-gray-900 font-semibold text-lg tracking-tight">{{ $item->label }}</h3>
        </div>
        <ul class="space-y-1">
            @foreach ($item->children as $child)
                <li class="relative" @if ($child->children->isNotEmpty()) x-data="{ sub: false }" @mouseenter="sub = true" @mouseleave="sub = false" @endif>
                    <a href="{{ $child->url ?? '#' }}" @if ($child->target) target="_blank" rel="noopener" @endif @if ($child->isActive) aria-current="page" @endif
                       class="group flex items-center justify-between gap-2 py-2 px-3 -mx-3 rounded-md text-sm w-full text-left transition-all duration-200 text-gray-600 hover:text-gray-900 hover:bg-gray-50 hover:no-underline">
                        <span class="leading-relaxed">{{ $child->label }}</span>
                        @if ($child->children->isNotEmpty())
                            <x-theme::icon name="chevron-right" width="16" height="16" class="shrink-0" />
                        @endif
                    </a>
                    @if ($child->children->isNotEmpty())
                        <ul class="absolute left-full top-0 ml-3 min-w-64 bg-white shadow-2xl border border-gray-100 rounded-lg p-4 space-y-1" x-show="sub" x-transition.opacity x-cloak>
                            @foreach ($child->children as $grandchild)
                                <li><a href="{{ $grandchild->url ?? '#' }}" @if ($grandchild->target) target="_blank" rel="noopener" @endif class="block py-2 px-3 rounded-md text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 hover:no-underline">{{ $grandchild->label }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>
