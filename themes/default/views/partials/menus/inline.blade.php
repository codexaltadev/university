<ul class="link-list link-list--inline">
    @foreach ($items as $item)
        <li>
            @if ($item->url)
                <a href="{{ $item->url }}" @if ($item->target) target="_blank" rel="noopener" @endif>{{ $item->label }}</a>
            @else
                <span>{{ $item->label }}</span>
            @endif
        </li>
    @endforeach
</ul>
