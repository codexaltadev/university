{{-- Social icons (Settings › Social, FR-SET-03) as DIU's footer shows them. Contract: $links (platform, url, label), $class --}}
<div class="social flex items-center flex-wrap gap-3 {{ $class ?? '' }}">
    @foreach ($links as $link)
        @continue(blank($link['url'] ?? null))
        <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex p-[9px] items-center justify-center rounded-full ring-1 ring-white/15 text-white hover:bg-white/10">
            <x-theme::icon :name="$link['platform'] ?? 'external'" width="24" height="24" />
            <span class="sr-only">{{ $link['label'] ?? ucfirst($link['platform'] ?? '') }}</span>
        </a>
    @endforeach
</div>
