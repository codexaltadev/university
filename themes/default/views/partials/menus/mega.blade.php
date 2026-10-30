{{-- Primary menu "mega" (DIU main site): 2nd level = column headings, 3rd = links; the panel spans the menu area. Items without the mega switch open the dropdown panel --}}
<nav class="nav nav--mega" aria-label="{{ __('theme::theme.primary_nav') }}" x-data="{ open: null }" @keydown.escape="open = null" @mouseleave="open = null">
    <ul class="flex items-center">
        @foreach ($items as $item)
            @if ($item->children->isNotEmpty() && $item->mega)
                <li class="static group" @mouseenter="open = {{ $loop->index }}" @focusout="if (! $el.contains($event.relatedTarget)) open = null">
                    <button type="button" class="nav__link block cursor-pointer px-2 xl:px-4 py-[22px] text-secondary font-semibold text-base transition-all duration-300 hover:font-bold" :class="{ 'underline underline-offset-4': open === {{ $loop->index }} }"
                            @click="open = open === {{ $loop->index }} ? null : {{ $loop->index }}" :aria-expanded="open === {{ $loop->index }}">{{ $item->label }}</button>
                    <div class="nav__panel--mega absolute top-full w-full left-0 right-0 rounded-b bg-white shadow-lg z-50" x-show="open === {{ $loop->index }}" x-transition.opacity x-cloak>
                        <div class="container mx-auto px-4 py-6">
                            <div class="grid gap-6" style="grid-template-columns: repeat({{ max(1, min(6, (int) ($item->columns ?: $item->children->count()))) }}, minmax(0, 1fr))">
                                @foreach ($item->children as $column)
                                    <div @class(['border-r border-gray-200 pr-6' => ! $loop->last])>
                                        @if ($column->url)
                                            <a href="{{ $column->url }}" class="mega__heading block font-semibold mb-3 text-base text-secondary border-b border-gray-200 pb-2 transition-colors duration-300 hover:text-blue-600 hover:no-underline">{{ $column->label }}</a>
                                        @else
                                            <h6 class="mega__heading font-semibold mb-3 text-base text-secondary border-b border-gray-200 pb-2 transition-colors duration-300 hover:text-blue-600">{{ $column->label }}</h6>
                                        @endif
                                        @foreach ($column->children as $link)
                                            <div>
                                                <a href="{{ $link->url ?? '#' }}" @if ($link->target) target="_blank" rel="noopener" @endif @if ($link->isActive) aria-current="page" @endif class="block px-4 py-2 text-neutral-700 text-sm transition-all duration-300 hover:bg-secondary hover:text-white hover:no-underline rounded">{{ $link->label }}</a>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </li>
            @elseif ($item->children->isNotEmpty())
                <li class="relative group" @mouseenter="open = {{ $loop->index }}" @focusout="if (! $el.contains($event.relatedTarget)) open = null">
                    <button type="button" class="nav__link block cursor-pointer px-2 xl:px-4 py-[22px] text-secondary font-semibold text-base transition-all duration-300 hover:font-bold" :class="{ 'underline underline-offset-4': open === {{ $loop->index }} }"
                            @click="open = open === {{ $loop->index }} ? null : {{ $loop->index }}" :aria-expanded="open === {{ $loop->index }}">{{ $item->label }}</button>
                    @include('theme::partials.menus.panel', ['item' => $item, 'index' => $loop->index])
                </li>
            @else
                <li class="static">
                    <a href="{{ $item->url }}" @if ($item->target) target="_blank" rel="noopener" @endif @if ($item->isActive) aria-current="page" @endif class="nav__link block px-2 xl:px-4 py-[22px] text-secondary font-semibold text-base transition-all duration-300 hover:font-bold hover:no-underline">{{ $item->label }}</a>
                </li>
            @endif
        @endforeach
    </ul>
</nav>
