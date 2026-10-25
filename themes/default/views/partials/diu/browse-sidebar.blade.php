{{--
    DIU Academics Sidebar: "Browse by Section (NN)" — tabs of the page (an Alpine "current" on the page
    switches them) and links (↗, external ones in a new tab), then Quick Links; on phones a box with the
    current one and See More. Contract: $items (list of [id, label, url = null for a tab, optional current (a link to the page shown) and
    arrow (false: no ↗ — sections of one listing)]), $quickLinks (list of [label, url]),
    $width (optional width classes; DIU Academics' 420px by default)
--}}
<aside class="hidden lg:block w-full {{ $width ?? 'lg:w-[420px]' }} shrink-0" aria-labelledby="browse-title">
    <div class="sticky top-32 space-y-6">
        <div class="rounded-xl">
            <h2 id="browse-title" class="text-xl font-bold border-b p-2 pb-2 border-gray-100 text-neutral-600">{{ __('theme::theme.program.browse') }} ({{ str_pad((string) count($items), 2, '0', STR_PAD_LEFT) }})</h2>
            <ul class="mt-4 border-l border-gray-400" role="list">
                @foreach ($items as $item)
                    <li>
                        @if ($item['url'])
                            <a href="{{ $item['url'] }}" @if (str_starts_with($item['url'], 'http')) target="_blank" rel="noopener noreferrer" @endif @if ($item['current'] ?? false) aria-current="page" @endif
                               @class(['group flex w-full text-[22px] font-medium items-center my-3 rounded-none p-[10px] text-left hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-600',
                                       'border-l-3 border-primary bg-blue-50 text-slate-900' => $item['current'] ?? false, 'text-slate-700 hover:bg-blue-50' => ! ($item['current'] ?? false)])>
                                <span class="truncate">{{ $item['label'] }}</span>
                                @if ($item['arrow'] ?? true)<x-theme::icon name="arrow-up-right" class="w-4 h-4 text-secondary" />@endif
                            </a>
                        @else
                            <button type="button" @click="current = @js($item['id'])" :aria-current="current === @js($item['id']) ? 'page' : null"
                                    class="group flex w-full text-[22px] text-neutral-800 font-medium items-center my-3 rounded-none p-[10px] text-left cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-600"
                                    :class="current === @js($item['id']) ? 'border-l-3 border-primary bg-blue-50 text-slate-900' : 'border-none text-slate-700 hover:bg-blue-50'">
                                <span class="truncate">{{ $item['label'] }}</span>
                            </button>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
        @include('theme::partials.diu.quick-links', ['links' => $quickLinks])
    </div>
</aside>

<div class="lg:hidden mb-5 border-b border-neutral-200 relative w-full" :class="more ? 'pb-16' : 'pb-5'">
    <div class="mb-6 sm:mb-8">
        <h2 class="text-xl sm:text-2xl font-semibold text-gray-600 pb-2 border-b border-gray-300">{{ __('theme::theme.program.browse') }}</h2>
    </div>
    <div class="bg-neutral-50 py-4 sm:p-6 mb-2 sm:mb-3 relative overflow-hidden">
        <div class="flex bg-sky-50 items-center mb-4 sm:mb-6">
            <div class="w-1 sm:w-1.5 h-8 bg-primary mr-4 sm:mr-6 mt-1 flex-shrink-0"></div>
            <h3 class="text-xl sm:text-3xl font-semibold text-secondary leading-tight">
                @foreach ($items as $item)
                    @if (! $item['url'])<span x-show="current === @js($item['id'])" @if (! $loop->first) x-cloak @endif>{{ $item['label'] }}</span>@elseif ($item['current'] ?? false)<span>{{ $item['label'] }}</span>@endif
                @endforeach
            </h3>
        </div>
        <div class="space-y-2 sm:space-y-3 ml-5 sm:ml-7 relative">
            @foreach ($items as $item)
                <a href="{{ $item['url'] ?: '#' }}" @if (! $item['url']) @click.prevent="current = @js($item['id']); more = false" @elseif (str_starts_with($item['url'], 'http')) target="_blank" rel="noopener noreferrer" @endif
                   @if ($loop->index >= 3) x-show="more" x-cloak @endif
                   class="flex items-center gap-5 text-lg sm:text-2xl font-medium transition-all duration-200 hover:no-underline"
                   :class="(more ? 'border-b py-2 border-neutral-200 ' : '') + (more || {{ $loop->index }} === 0 ? 'text-neutral-800' : 'text-gray-400 pointer-events-none') + (! more && {{ $loop->index }} === 2 ? ' opacity-0' : '')">
                    <span>{{ $item['label'] }}</span>
                    @if ($item['url'] && ($item['arrow'] ?? true))<x-theme::icon name="arrow-up-right" class="w-5 h-5 sm:w-6 sm:h-6 text-secondary flex-shrink-0" />@endif
                </a>
            @endforeach
        </div>
        <div x-show="! more" class="absolute bottom-0 left-0 right-0 h-16 bg-gradient-to-t from-white via-white/60 to-transparent pointer-events-none"></div>
    </div>
    <div x-show="more" x-cloak>
        @include('theme::partials.diu.quick-links', ['links' => $quickLinks])
    </div>
    <div class="text-center absolute mt-2 w-full" :class="more ? 'bottom-5' : 'bottom-9'">
        <button type="button" @click="more = ! more" class="flex items-center justify-center mx-auto text-gray-700 hover:text-gray-900 transition-colors pb-1 cursor-pointer">
            <span class="mr-2 border-b-2 border-gray-700 font-medium text-base sm:text-lg" x-text="more ? @js(__('theme::theme.program.see_less')) : @js(__('theme::theme.program.see_more'))">{{ __('theme::theme.program.see_more') }}</span>
            <x-theme::icon name="chevron-down" class="w-4 h-4 sm:w-5 sm:h-5 transition-transform" ::class="more && 'rotate-180'" />
        </button>
    </div>
</div>
