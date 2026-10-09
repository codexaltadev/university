<ul @class(['nav__list' => $level === 1, 'nav__sub' => $level > 1])>
    @foreach ($items as $item)
        <li class="nav__item">
            @if ($item->url)
                <a href="{{ $item->url }}" class="nav__link" @if ($item->target) target="_blank" rel="noopener" @endif @if ($item->isActive) aria-current="page" @endif>{{ $item->label }}</a>
            @else
                <span class="nav__link">{{ $item->label }}</span>
            @endif
            @if ($item->children->isNotEmpty())
                @include('theme::partials.menus.tree', ['items' => $item->children, 'level' => $level + 1])
            @endif
        </li>
    @endforeach
</ul>
