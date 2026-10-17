{{-- Primary menu in the mobile menu (DIU MobileMegaMenu): a mega item lists its columns (orange headings, links under them); other items nest as accordions --}}
<nav class="drawer-nav space-y-1" aria-label="{{ __('theme::theme.primary_nav') }}">
    @foreach ($items as $item)
        @if ($item->children->isNotEmpty() && $item->mega)
            <div class="border-b border-gray-200" x-data="{ open: false }">
                <button type="button" @click="open = ! open" :aria-expanded="open" class="w-full flex items-center justify-between py-3 text-gray-800 hover:text-blue-600 font-medium text-base transition-all duration-300 cursor-pointer">
                    {{ $item->label }}
                    <x-theme::icon name="chevron-down" class="w-5 h-5 transition-transform duration-300" x-bind:class="{ 'rotate-180': open }" />
                </button>
                <div x-show="open" x-collapse x-cloak>
                    <div class="pl-4 pb-4 space-y-4">
                        @foreach ($item->children as $column)
                            <div class="space-y-2">
                                <h4 class="text-sm font-semibold text-amber-500">{{ $column->label }}</h4>
                                <ul class="space-y-1.5">
                                    @foreach ($column->children as $link)
                                        <li><a href="{{ $link->url ?? '#' }}" @if ($link->target) target="_blank" rel="noopener" @endif class="text-sm text-gray-700 hover:text-primary hover:no-underline transition-colors duration-200 block py-1">{{ $link->label }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            @include('theme::partials.menus.offcanvas-list', ['item' => $item, 'depth' => 0])
        @endif
    @endforeach
</nav>
