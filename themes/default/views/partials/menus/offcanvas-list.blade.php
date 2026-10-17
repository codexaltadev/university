<ul class="drawer-nav__list drawer-nav__list--{{ $level }}">
    @foreach ($items as $item)
        <li class="drawer-nav__item" @if ($item->children->isNotEmpty()) x-data="{ open: false }" @endif>
            @if ($item->children->isNotEmpty())
                <button type="button" class="drawer-nav__link" @click="open = ! open" :aria-expanded="open">
                    {{ $item->label }}
                    <x-theme::icon name="chevron-down" width="18" height="18" class="drawer-nav__caret" x-bind:class="{ 'is-open': open }" />
                </button>
                <div x-show="open" x-collapse x-cloak>
                    @if ($item->url)
                        <a href="{{ $item->url }}" class="drawer-nav__link drawer-nav__link--overview">{{ $item->label }}</a>
                    @endif
                    @include('theme::partials.menus.offcanvas-list', ['items' => $item->children, 'level' => $level + 1])
                </div>
            @elseif ($item->url)
                <a href="{{ $item->url }}" class="drawer-nav__link" @if ($item->target) target="_blank" rel="noopener" @endif @if ($item->isActive) aria-current="page" @endif>{{ $item->label }}</a>
            @else
                <span class="drawer-nav__link">{{ $item->label }}</span>
            @endif
        </li>
    @endforeach
</ul>
