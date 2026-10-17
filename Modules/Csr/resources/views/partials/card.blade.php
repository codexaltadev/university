{{-- One CSR activity. Contract: $activity, $base (microsite path or ''), $layout (cards / grid / timeline) --}}
@php($href = url($base.'/'.\Modules\Csr\Models\Activity::PATH.'/'.$activity->slug()))
@php($image = $activity->firstMedia('image'))
@php($date = $activity->occurred_on ?? $activity->active_at)
<article class="csr-activity csr-activity--{{ $layout }}" style="{{ $layout === 'timeline'
    ? 'position:relative;display:flex;gap:var(--space-4, 16px);margin-bottom:var(--space-6, 24px)'
    : 'border:1px solid var(--color-border);border-radius:var(--radius, 8px);overflow:hidden;height:100%' }}">
    @if ($image)
        <a href="{{ $href }}" style="{{ $layout === 'timeline' ? 'flex:0 0 160px' : 'display:block' }}"><img src="{{ $image->url('medium') ?? $image->url() }}" alt="{{ $image->alt_text ?: $activity->title }}" loading="lazy" style="width:100%;aspect-ratio:{{ $layout === 'grid' ? '1/1' : '16/10' }};object-fit:cover;{{ $layout === 'timeline' ? 'border-radius:var(--radius, 8px)' : '' }}"></a>
    @endif
    <div style="{{ $layout === 'timeline' ? '' : 'padding:var(--space-4, 16px)' }}">
        @if ($date)<time datetime="{{ $date->toDateString() }}" style="font-size:.85rem;opacity:.75">{{ $date->translatedFormat('j F Y') }}</time>@endif
        <h3 style="margin:4px 0;font-size:{{ $layout === 'grid' ? '1rem' : '1.1rem' }}"><a href="{{ $href }}">{{ $activity->title }}</a></h3>
        @if ($activity->summary && $layout !== 'grid')<p style="margin:0">{{ \Illuminate\Support\Str::limit($activity->summary, 180) }}</p>@endif
    </div>
</article>
