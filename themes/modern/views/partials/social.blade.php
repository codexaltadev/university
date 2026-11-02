{{-- Social links (Settings › Social, FR-SET-03). Contract: $links (platform, url, label), $class --}}
@if (collect($links)->filter(fn ($link) => filled($link['url'] ?? null))->isNotEmpty())
    <div class="social {{ $class ?? '' }}" aria-label="{{ __('theme::theme.follow_us') }}">
        @foreach ($links as $link)
            @continue(blank($link['url'] ?? null))
            <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">
                <x-theme::icon :name="$link['platform'] ?? 'external'" width="18" height="18" />
                <span class="sr-only">{{ $link['label'] ?? ucfirst($link['platform'] ?? '') }}</span>
            </a>
        @endforeach
    </div>
@endif
