{{-- A menu in the drawer: every level opens as an accordion. Contract: $items --}}
<ul class="drawer-menu">
    @foreach ($items as $item)
        <li x-data="{ sub: false }">
            @if ($item->children->isNotEmpty())
                <button type="button" @click="sub = ! sub" :aria-expanded="sub">{{ $item->label }}<x-theme::icon name="chevron-down" width="18" height="18" /></button>
                <div x-show="sub" x-cloak>
                    @include('theme::partials.menus.drawer', ['items' => $item->children])
                </div>
            @elseif ($item->url)
                <a href="{{ $item->url }}" @if ($item->target) target="_blank" rel="noopener" @endif @if ($item->isActive) aria-current="page" @endif>{{ $item->label }}</a>
            @endif
        </li>
    @endforeach
</ul>
