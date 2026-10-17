{{-- Social icons (Settings › Social, FR-SET-03). Contract: $links (platform, url, label), $class --}}
<ul class="social {{ $class ?? '' }}">
    @foreach ($links as $link)
        @continue(blank($link['url'] ?? null))
        <li>
            <a href="{{ $link['url'] }}" target="_blank" rel="noopener" class="social__link">
                <x-theme::icon :name="$link['platform'] ?? 'external'" width="16" height="16" />
                <span class="sr-only">{{ $link['label'] ?? ucfirst($link['platform'] ?? '') }}</span>
            </a>
        </li>
    @endforeach
</ul>
