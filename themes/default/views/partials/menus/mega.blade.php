{{-- Primary menu "mega": 2nd level = column headings, 3rd = links; picture, text and button per panel. The panel spans the header --}}
<nav class="nav nav--mega" aria-label="{{ __('theme::theme.primary_nav') }}" x-data="{ open: null }" @keydown.escape="open = null">
    <ul class="nav__list">
        @foreach ($items as $item)
            <li @class(['nav__item', 'nav__item--mega' => $item->children->isNotEmpty() && $item->mega]) @mouseleave="open = null" @focusout="if (! $el.contains($event.relatedTarget)) open = null">
                @if ($item->children->isNotEmpty())
                    <button type="button" @class(['nav__link', 'is-active' => $item->isActive]) :aria-expanded="open === {{ $loop->index }}"
                            @click="open = open === {{ $loop->index }} ? null : {{ $loop->index }}" @mouseenter="open = {{ $loop->index }}">
                        {{ $item->label }}<x-theme::icon name="chevron-down" width="16" height="16" class="nav__caret" />
                    </button>
                @endif
                @if ($item->children->isNotEmpty() && $item->mega)
                    <div class="nav__panel nav__panel--mega" x-show="open === {{ $loop->index }}" x-transition.opacity x-cloak style="--mega-columns: {{ (int) ($item->columns ?: 4) }}">
                        <div class="mega__columns">
                            @foreach ($item->children as $column)
                                <div class="mega__column">
                                    @if ($column->children->isEmpty())
                                        {{-- a plain link in the grid (two-level mega menu) --}}
                                        <a href="{{ $column->url ?? '#' }}" class="mega__link" @if ($column->target) target="_blank" rel="noopener" @endif @if ($column->isActive) aria-current="page" @endif>{{ $column->label }}<x-theme::icon name="arrow-right" width="16" height="16" /></a>
                                    @else
                                        @if ($column->url)
                                            <a href="{{ $column->url }}" class="mega__heading">{{ $column->label }}</a>
                                        @else
                                            <span class="mega__heading">{{ $column->label }}</span>
                                        @endif
                                        @include('theme::partials.menus.list', ['items' => $column->children])
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @if ($item->image || $item->description || $item->cta)
                            <div class="mega__aside">
                                @if ($item->image)
                                    <img src="{{ $item->image }}" alt="" loading="lazy" class="mega__image">
                                @endif
                                @if ($item->description)
                                    <p>{{ $item->description }}</p>
                                @endif
                                @if (is_array($item->cta) && ! empty($item->cta['url']))
                                    <a href="{{ $item->cta['url'] }}" class="btn btn--primary btn--sm">{{ $item->cta['label'] ?? $item->label }}</a>
                                @endif
                            </div>
                        @endif
                    </div>
                @elseif ($item->children->isNotEmpty())
                    <div class="nav__panel" x-show="open === {{ $loop->index }}" x-transition.opacity x-cloak>
                        @include('theme::partials.menus.tree', ['items' => $item->children, 'level' => 2])
                    </div>
                @else
                    <a href="{{ $item->url }}" class="nav__link" @if ($item->target) target="_blank" rel="noopener" @endif @if ($item->isActive) aria-current="page" @endif>{{ $item->label }}</a>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
