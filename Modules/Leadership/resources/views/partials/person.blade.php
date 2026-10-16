{{-- One holder as a card (grid / cards) or a row (list). Contract: $term, $position, $layout --}}
@php($person = $term->person())
@php($size = $layout === 'cards' ? '160px' : ($layout === 'list' ? '72px' : '120px'))
<article class="leadership-person" style="display:flex;{{ $layout === 'list' ? 'flex-direction:row;align-items:center' : 'flex-direction:column;align-items:center;text-align:center' }};gap:var(--space-3, 12px);padding:var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius-md, 8px)">
    @if ($photo = $person->photoUrl())
        <img src="{{ $photo }}" alt="" loading="lazy" style="width:{{ $size }};height:{{ $size }};border-radius:{{ $layout === 'cards' ? 'var(--radius-md, 8px)' : '50%' }};object-fit:cover">
    @else
        <span aria-hidden="true" style="display:grid;place-items:center;width:{{ $size }};height:{{ $size }};border-radius:50%;background:var(--color-surface, #F3F4F9);font-weight:700;font-size:1.6em">{{ mb_substr($person->name(), 0, 1) }}</span>
    @endif
    <div>
        <h3 style="margin:0;font-size:1.05em"><a href="{{ url($term->url()) }}">{{ $person->name() }}</a></h3>
        <p style="margin:2px 0">{{ $position->title }}</p>
        @if ($layout !== 'grid' && filled($term->message))
            <p style="margin:6px 0 0;color:var(--color-muted, #6b7280)">{{ \Illuminate\Support\Str::words(strip_tags((string) $term->message), 30) }}</p>
        @endif
    </div>
</article>
