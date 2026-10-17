<ul @class(['space-y-1' => $level === 1, 'pl-4 pb-2 space-y-1' => $level > 1])>
    @foreach ($items as $item)
        <li @class(['border-b border-gray-200/50' => $level === 1]) @if ($item->children->isNotEmpty()) x-data="{ open: false }" @endif>
            @if ($item->children->isNotEmpty())
                <button type="button" @click="open = ! open" :aria-expanded="open"
                        @class(['w-full flex items-center justify-between text-left cursor-pointer transition-all duration-300 hover:text-blue-600',
                                'py-3 text-gray-800 font-medium text-base' => $level === 1,
                                'py-2 text-gray-700 text-sm font-medium' => $level > 1])>
                    {{ $item->label }}
                    <x-theme::icon name="chevron-down" width="18" height="18" class="transition-transform duration-300" x-bind:class="{ 'rotate-180': open }" />
                </button>
                <div x-show="open" x-collapse x-cloak>
                    @if ($item->url)
                        <a href="{{ $item->url }}" class="block py-2 pl-4 text-sm text-gray-600 hover:text-blue-600 hover:no-underline">{{ $item->label }}</a>
                    @endif
                    @include('theme::partials.menus.offcanvas-list', ['items' => $item->children, 'level' => $level + 1])
                </div>
            @elseif ($item->url)
                <a href="{{ $item->url }}" @if ($item->target) target="_blank" rel="noopener" @endif @if ($item->isActive) aria-current="page" @endif
                   @class(['block transition-all duration-300 hover:text-blue-600 hover:no-underline',
                           'py-3 text-gray-800 font-medium text-base' => $level === 1,
                           'py-2 text-sm text-gray-600' => $level > 1])>{{ $item->label }}</a>
            @else
                <span @class(['block', 'py-3 text-gray-800 font-medium' => $level === 1, 'py-2 text-sm font-semibold text-gray-700' => $level > 1])>{{ $item->label }}</span>
            @endif
        </li>
    @endforeach
</ul>
