{{-- Primary menu "dropdown" (DIU department menu): grey links with a chevron; each opens its panel under it --}}
<nav class="nav {{ $navClass ?? 'nav--dropdown' }}" aria-label="{{ __('theme::theme.primary_nav') }}" x-data="{ open: null }" @keydown.escape="open = null">
    <ul class="flex items-center">
        @foreach ($items as $item)
            <li class="relative py-6 mx-1.5 2xl:mx-2" @mouseenter="open = {{ $loop->index }}" @mouseleave="open = null" @focusout="if (! $el.contains($event.relatedTarget)) open = null">
                @if ($item->children->isNotEmpty())
                    <button type="button" class="nav__link text-gray-700 hover:text-secondary cursor-pointer font-medium transition-all duration-300 flex items-center gap-2"
                            @click="open = open === {{ $loop->index }} ? null : {{ $loop->index }}" :aria-expanded="open === {{ $loop->index }}">
                        {{ $item->label }}<x-theme::icon name="chevron-down" width="16" height="16" class="transition-transform duration-300" x-bind:class="{ 'rotate-180': open === {{ $loop->index }} }" />
                    </button>
                    @include('theme::partials.menus.panel', ['item' => $item, 'index' => $loop->index])
                @else
                    <a href="{{ $item->url }}" @if ($item->target) target="_blank" rel="noopener" @endif @if ($item->isActive) aria-current="page" @endif class="nav__link text-gray-700 hover:text-secondary hover:no-underline cursor-pointer font-medium transition-all duration-300">{{ $item->label }}</a>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
