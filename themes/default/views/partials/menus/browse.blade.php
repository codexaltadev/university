{{--
    A page's sidebar menu as DIU's "Browse by Section (NN)" (DIUFronend admission/sidebar): big items, the page shown with a
    blue bar, ↗ on those that open in a new tab, an item with children opens them below (open on its own pages).
    Contract (x-platform::menu): $items (label, url, target, isActive, children)
--}}
<div class="rounded-xl">
    <h2 class="text-xl font-bold border-b pb-2 border-gray-100 text-slate-700">{{ __('theme::theme.program.browse') }} ({{ str_pad((string) count($items), 2, '0', STR_PAD_LEFT) }})</h2>
    <ul class="mt-4" role="list">
        @foreach ($items as $item)
            <li class="mb-1" x-data="{ open: @js($item->isActive) }">
                @if ($item->children->isNotEmpty())
                    <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open"
                            @class(['group flex w-full my-3 text-[22px] font-medium items-center gap-2 border-l-4 px-3 py-2 text-left cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-600', 'border-secondary bg-sky-50 text-slate-900' => $item->isActive, 'border-transparent text-slate-700 hover:bg-slate-50' => ! $item->isActive])>
                        <span class="truncate">{{ $item->label }}</span>
                        <x-theme::icon name="chevron-right" class="w-4 h-4 text-gray-500 transition-transform" x-bind:class="open && 'rotate-90'" />
                    </button>
                    <ul class="ml-5 mt-1 border-l border-gray-200" x-show="open" x-cloak x-collapse>
                        @if ($item->url)
                            <li><a href="{{ $item->url }}" class="block px-3 py-2 my-3 text-lg text-slate-600 hover:bg-slate-50 hover:no-underline">{{ $item->label }}</a></li>
                        @endif
                        @foreach ($item->children as $child)
                            <li>
                                <a href="{{ $child->url ?? '#' }}" @if ($child->target) target="_blank" rel="noopener" @endif @if ($child->isActive) aria-current="page" @endif
                                   @class(['flex w-full items-center gap-2 px-3 py-2 my-3 text-lg text-left hover:no-underline', 'border-l-4 border-secondary bg-sky-100 text-slate-900' => $child->isActive, 'text-slate-600 hover:bg-slate-50' => ! $child->isActive])>
                                    <span class="truncate">{{ $child->label }}</span>
                                    @if ($child->target)<x-theme::icon name="arrow-up-right" class="w-4 h-4 text-primary shrink-0" />@endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <a href="{{ $item->url ?? '#' }}" @if ($item->target) target="_blank" rel="noopener" @endif @if ($item->isActive) aria-current="page" @endif
                       @class(['group flex w-full my-3 text-[22px] font-medium items-center gap-2 border-l-4 px-3 py-2 text-left hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-600', 'border-secondary bg-sky-50 text-slate-900' => $item->isActive, 'border-transparent text-slate-700 hover:bg-slate-50' => ! $item->isActive])>
                        <span class="truncate">{{ $item->label }}</span>
                        @if ($item->target)<x-theme::icon name="arrow-up-right" class="w-4 h-4 text-primary shrink-0" />@endif
                    </a>
                @endif
            </li>
        @endforeach
    </ul>
</div>
