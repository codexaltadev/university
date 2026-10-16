{{-- An album in a list: cover, title, date, how many. Contract: $album, $base (microsite path or ''), $layout (cards / grid) --}}
@php($count = $album->photoCount() + $album->videos->where('is_active', true)->count())
<a href="{{ url($base !== '' ? $base.'/'.\Modules\Gallery\Models\Album::PATH.'/'.$album->slug() : $album->url()) }}" class="gallery-card" style="display:block;color:inherit;text-decoration:none">
    @if ($cover = $album->coverUrl('medium'))
        <img src="{{ $cover }}" alt="" loading="lazy" style="width:100%;aspect-ratio:{{ $layout === 'grid' ? '1/1' : '4/3' }};object-fit:cover;border-radius:var(--radius-sm, 4px)">
    @else
        <div style="aspect-ratio:4/3;background:var(--color-border);border-radius:var(--radius-sm, 4px)"></div>
    @endif
    <strong style="display:block;margin-top:var(--space-2, 8px)">{{ $album->title }}</strong>
    @if ($layout === 'cards')
        <small style="color:var(--color-muted, #6b7280)">
            @if ($album->occurred_on){{ $album->occurred_on->translatedFormat('j F Y') }} · @endif{{ trans_choice('gallery::gallery.counts', $count, ['count' => $count]) }}
        </small>
    @endif
</a>
