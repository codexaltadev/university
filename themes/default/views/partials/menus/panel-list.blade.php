{{-- Links of a dropdown panel; a link with children shows a grey chevron and opens its panel beside (DIU MenuItem). Contract: $links --}}
<ul class="space-y-1">
    @foreach ($links as $link)
        <li class="relative" @if ($link->children->isNotEmpty()) x-data="{ sub: false }" @mouseenter="sub = true" @mouseleave="sub = false" @focusin="sub = true" @endif>
            <a href="{{ $link->url ?? '#' }}" @if ($link->target) target="_blank" rel="noopener noreferrer" @endif @if ($link->isActive) aria-current="page" @endif
               class="group flex items-center justify-between gap-2 py-2 px-3 -mx-3 rounded-md text-sm w-full text-left transition-all duration-200 text-gray-600 hover:text-gray-900 hover:bg-gray-50 hover:no-underline"
               @if ($link->children->isNotEmpty()) :class="{ '!text-gray-900 bg-gray-50 font-medium': sub }" @endif>
                <span class="leading-relaxed">{{ $link->label }}</span>
                <span class="flex items-center shrink-0">
                    @if ($link->children->isNotEmpty())
                        <x-theme::icon name="chevron-right" class="w-4 h-4 text-gray-400 transition-transform duration-200" x-bind:class="{ '!text-gray-900 translate-x-0.5': sub }" />
                    @else
                        <x-theme::icon name="chevron-right" class="w-4 h-4 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-200" />
                    @endif
                </span>
            </a>
            @if ($link->children->isNotEmpty())
                <div class="absolute left-full top-[-0.5rem] pl-2 z-50 min-w-64 max-w-xs" x-show="sub" x-transition.opacity x-cloak>
                    <div class="bg-white shadow-2xl border border-gray-100 rounded-lg px-6 py-6">
                        <div class="pb-2 border-b border-gray-100 mb-3">
                            <h4 class="text-gray-900 font-semibold text-sm tracking-tight">{{ $link->label }}</h4>
                        </div>
                        @include('theme::partials.menus.panel-list', ['links' => $link->children])
                    </div>
                </div>
            @endif
        </li>
    @endforeach
</ul>
