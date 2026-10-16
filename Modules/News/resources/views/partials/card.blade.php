{{-- One post as a card (grid / cards / magazine) or a row (list). Contract: $post, $layout, $base (place path or '') --}}
@php($image = $post->featuredImage())
@php($href = url($base.'/'.\Modules\News\Models\Post::PATH.'/'.$post->slug()))
<article class="news-card" style="display:flex;{{ $layout === 'list' ? 'flex-direction:row;gap:var(--space-4, 16px)' : 'flex-direction:column' }};border:1px solid var(--color-border);border-radius:var(--radius-md, 8px);overflow:hidden;background:var(--color-surface, #fff)">
    @if ($image)
        <a href="{{ $href }}" style="display:block;{{ $layout === 'list' ? 'flex:0 0 220px' : '' }};aspect-ratio:16/9;overflow:hidden">
            <img src="{{ $image->url('medium') }}" alt="{{ $image->alt ?? '' }}" loading="lazy" style="width:100%;height:100%;object-fit:cover">
        </a>
    @endif
    <div style="padding:var(--space-4, 16px);display:flex;flex-direction:column;gap:var(--space-2, 8px)">
        <time datetime="{{ $post->shownAt()?->toIso8601String() }}" style="font-size:.85em;color:var(--color-muted, #6b7280)">{{ $post->shownAt()?->translatedFormat('j F Y') }}</time>
        <h3 style="margin:0;font-size:1.1em;line-height:1.35"><a href="{{ $href }}">{{ $post->title }}</a></h3>
        @if ($layout !== 'grid' && filled($post->excerpt))
            <p style="margin:0">{{ \Illuminate\Support\Str::limit((string) $post->excerpt, 180) }}</p>
        @endif
    </div>
</article>
