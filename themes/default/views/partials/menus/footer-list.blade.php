{{-- A footer link column (DIU): white links, underline on hover --}}
<ul class="space-y-2 text-white/80">
    @foreach ($items as $item)
        <li class="mt-4">
            @if ($item->url)
                <a href="{{ $item->url }}" @if ($item->target) target="_blank" rel="noopener" @endif class="text-base text-white/80 hover:text-white hover:underline">{{ $item->label }}</a>
            @else
                <span class="text-base">{{ $item->label }}</span>
            @endif
        </li>
    @endforeach
</ul>
