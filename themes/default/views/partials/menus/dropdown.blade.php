{{-- Primary menu "dropdown": the second level opens under the item (Alpine; hover, click and keyboard; Esc or leaving closes) --}}
<nav class="nav nav--dropdown" aria-label="{{ __('theme::theme.primary_nav') }}" x-data="{ open: null }" @keydown.escape="open = null">
    <ul class="nav__list">
        @foreach ($items as $item)
            <li class="nav__item" @mouseleave="open = null" @focusout="if (! $el.contains($event.relatedTarget)) open = null">
                @if ($item->children->isNotEmpty())
                    <button type="button" @class(['nav__link', 'is-active' => $item->isActive]) :aria-expanded="open === {{ $loop->index }}"
                            @click="open = open === {{ $loop->index }} ? null : {{ $loop->index }}" @mouseenter="open = {{ $loop->index }}">
                        {{ $item->label }}<x-theme::icon name="chevron-down" width="16" height="16" class="nav__caret" />
                    </button>
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
