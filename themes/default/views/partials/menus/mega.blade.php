{{-- Primary menu "mega": 2nd level = column headings, 3rd = links; picture, text and button per panel --}}
<nav class="nav nav--mega" aria-label="{{ __('theme::theme.primary_nav') }}" x-data="{ open: null }" @keydown.escape="open = null">
    <ul class="nav__list">
        @foreach ($items as $item)
            <li class="nav__item" @mouseleave="open = null">
                @if ($item->children->isNotEmpty() && $item->mega)
                    <button type="button" class="nav__link" :aria-expanded="open === {{ $loop->index }}"
                            @click="open = open === {{ $loop->index }} ? null : {{ $loop->index }}" @mouseenter="open = {{ $loop->index }}">
                        {{ $item->label }}
                    </button>
                    <div class="nav__panel nav__panel--mega" x-show="open === {{ $loop->index }}" x-cloak style="--mega-columns: {{ (int) ($item->columns ?: 4) }}">
                        <div class="mega__columns">
                            @foreach ($item->children as $column)
                                <div class="mega__column">
                                    @if ($column->url)
                                        <a href="{{ $column->url }}" class="mega__heading">{{ $column->label }}</a>
                                    @else
                                        <span class="mega__heading">{{ $column->label }}</span>
                                    @endif
                                    @include('theme::partials.menus.list', ['items' => $column->children])
                                </div>
                            @endforeach
                        </div>
                        @if ($item->image || $item->description || $item->cta)
                            <div class="mega__aside">
                                @if ($item->image)
                                    <img src="{{ $item->image }}" alt="" loading="lazy">
                                @endif
                                @if ($item->description)
                                    <p>{{ $item->description }}</p>
                                @endif
                                @if (is_array($item->cta) && ! empty($item->cta['url']))
                                    <a href="{{ $item->cta['url'] }}" class="btn btn--primary">{{ $item->cta['label'] ?? $item->label }}</a>
                                @endif
                            </div>
                        @endif
                    </div>
                @elseif ($item->children->isNotEmpty())
                    <button type="button" class="nav__link" :aria-expanded="open === {{ $loop->index }}"
                            @click="open = open === {{ $loop->index }} ? null : {{ $loop->index }}" @mouseenter="open = {{ $loop->index }}">
                        {{ $item->label }}
                    </button>
                    <div class="nav__panel" x-show="open === {{ $loop->index }}" x-cloak>
                        @include('theme::partials.menus.tree', ['items' => $item->children, 'level' => 2])
                    </div>
                @else
                    <a href="{{ $item->url }}" class="nav__link" @if ($item->target) target="_blank" rel="noopener" @endif @if ($item->isActive) aria-current="page" @endif>{{ $item->label }}</a>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
