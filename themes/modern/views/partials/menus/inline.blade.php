{{-- One row of links (utility row). Contract: $items --}}
<nav aria-label="{{ __('theme::theme.utility_nav') }}">
    <ul>
        @foreach ($items as $item)
            <li><a href="{{ $item->url }}" @if ($item->target) target="_blank" rel="noopener" @endif @if ($item->isActive) aria-current="page" @endif>{{ $item->label }}</a></li>
        @endforeach
    </ul>
</nav>
