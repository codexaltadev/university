{{-- /international/visitors (FR-INTL-06). Contract: $visitors (paginator, newest first), $base --}}
<x-theme::layout :title="__('international::international.visitors')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('international::international.visitors') }}</h1>
        @if ($visitors->isEmpty())<p style="margin-top:var(--space-8, 32px)">{{ __('international::international.none') }}</p>@endif
        <div style="margin-top:var(--space-6, 24px);display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(280px,1fr))">
            @foreach ($visitors as $visitor)
                @php($photo = $visitor->firstMedia('photo'))
                @php($album = $visitor->album())
                <article id="visitor-{{ $visitor->id }}" style="border:1px solid var(--color-border);border-radius:var(--radius, 8px);overflow:hidden">
                    @if ($photo)<img src="{{ $photo->url('medium') ?? $photo->url() }}" alt="{{ $photo->alt_text ?: $visitor->name }}" loading="lazy" style="width:100%;aspect-ratio:4/3;object-fit:cover">@endif
                    <div style="padding:var(--space-4, 16px)">
                        <time datetime="{{ $visitor->visited_on->toDateString() }}" style="font-size:.85rem;opacity:.8">{{ $visitor->visited_on->translatedFormat('j F Y') }}</time>
                        <h3 style="margin:4px 0;font-size:1.05rem">{{ $visitor->name }}</h3>
                        @if ($visitor->designation || $visitor->organization)<p style="margin:0">{{ collect([$visitor->designation, $visitor->organization])->filter()->implode(', ') }}</p>@endif
                        @if ($visitor->country)<p style="margin:4px 0 0;font-size:.9rem">{{ $visitor->country->emoji() }} {{ $visitor->country->name }}</p>@endif
                        @if ($visitor->body)
                            <details style="margin-top:var(--space-2, 8px)"><summary>{{ __('international::international.see_all') }}</summary><div class="prose">{!! $visitor->body !!}</div></details>
                        @endif
                        @if ($album && method_exists($album, 'url'))<p style="margin:var(--space-2, 8px) 0 0"><a href="{{ $album->url() }}">{{ __('international::international.album') }}</a></p>@endif
                    </div>
                </article>
            @endforeach
        </div>
        <div style="margin-top:var(--space-6, 24px)">{{ $visitors->links() }}</div>
    </div>
</x-theme::layout>
