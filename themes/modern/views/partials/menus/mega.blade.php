{{--
    Primary menu (Theme Guide §7.2): a top item with children opens a full-width panel — each child a column
    (its own children as links), or one list when the children have none. Contract: $items (resolved tree)
--}}
<nav class="mega-nav" aria-label="{{ __('theme::theme.primary_nav') }}">
    <ul>
        @foreach ($items as $item)
            <li>
                @if ($item->children->isNotEmpty())
                    <button type="button" class="mega-nav__link" @click="toggle({{ $loop->index }})" :aria-expanded="open === {{ $loop->index }}">
                        {{ $item->label }}<x-theme::icon name="chevron-down" width="16" height="16" />
                    </button>
                    <div class="mega-panel" x-show="open === {{ $loop->index }}" x-cloak x-transition.opacity.duration.150ms>
                        <div class="container mega-panel__inner">
                            @if ($item->children->contains(fn ($child) => $child->children->isNotEmpty()))
                                @foreach ($item->children as $column)
                                    <div>
                                        <p class="mega-panel__title">
                                            @if ($column->url)<a href="{{ $column->url }}" @if ($column->target) target="_blank" rel="noopener" @endif>{{ $column->label }}</a>@else{{ $column->label }}@endif
                                        </p>
                                        <ul>
                                            @foreach ($column->children as $link)
                                                <li><a href="{{ $link->url }}" @if ($link->target) target="_blank" rel="noopener" @endif @if ($link->isActive) aria-current="page" @endif>{{ $link->label }}</a></li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            @else
                                @foreach ($item->children->chunk(max(1, (int) ceil($item->children->count() / 3))) as $chunk)
                                    <ul class="mega-panel__single">
                                        @foreach ($chunk as $link)
                                            <li><a href="{{ $link->url }}" @if ($link->target) target="_blank" rel="noopener" @endif @if ($link->isActive) aria-current="page" @endif>{{ $link->label }}</a></li>
                                        @endforeach
                                    </ul>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @else
                    <a href="{{ $item->url }}" class="mega-nav__link" @if ($item->target) target="_blank" rel="noopener" @endif @if ($item->isActive) aria-current="page" @endif>{{ $item->label }}</a>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
