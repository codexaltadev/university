{{-- Any other menu (sidebar …): nested lists, the current page marked. Contract: $items --}}
<ul>
    @foreach ($items as $item)
        <li>
            @if ($item->url)
                <a href="{{ $item->url }}" @if ($item->target) target="_blank" rel="noopener" @endif @if ($item->isActive) aria-current="page" @endif>{{ $item->label }}</a>
            @else
                <span>{{ $item->label }}</span>
            @endif
            @if ($item->children->isNotEmpty())
                @include('theme::partials.menus.dropdown', ['items' => $item->children])
            @endif
        </li>
    @endforeach
</ul>
