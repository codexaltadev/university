{{-- One achievement in a list. Contract: $achievement, $base (microsite path or ''), $layout (cards / timeline / grid) --}}
@php($href = url($base !== '' ? $base.'/'.\Modules\Achievements\Models\Achievement::PATH.'/'.$achievement->slug() : $achievement->url()))
@php($picture = $achievement->firstMedia('image'))
<article class="achievement-card achievement-card--{{ $layout }}" style="{{ $layout === 'timeline' ? 'display:grid;grid-template-columns:110px 1fr;gap:var(--space-4, 16px);padding-bottom:var(--space-5, 20px);border-inline-start:3px solid var(--color-primary, #002652);padding-inline-start:var(--space-4, 16px)' : 'height:100%' }}">
    @if ($layout === 'timeline')
        <time datetime="{{ $achievement->achieved_on->toDateString() }}" style="font-weight:700">{{ $achievement->achieved_on->translatedFormat('M Y') }}</time>
    @endif
    <div>
        @if ($picture && $layout !== 'timeline')
            <a href="{{ $href }}"><img src="{{ $picture->url('medium') ?? $picture->url() }}" alt="{{ $picture->alt_text ?: $achievement->title }}" loading="lazy"
                 style="width:100%;aspect-ratio:{{ $layout === 'grid' ? '1/1' : '16/10' }};object-fit:cover;border-radius:var(--radius-sm, 4px)"></a>
        @endif
        <p style="margin:var(--space-2, 8px) 0 0;font-size:.8em;text-transform:uppercase;letter-spacing:.04em;color:var(--color-muted, #6b7280)">
            {{ __("achievements::achievements.levels.{$achievement->level}") }}@if ($achievement->position_label) · {{ $achievement->position_label }}@endif
        </p>
        <h3 style="margin:var(--space-1, 4px) 0;font-size:1.05rem"><a href="{{ $href }}">{{ $achievement->title }}</a></h3>
        @if ($layout !== 'grid')
            @if ($achievement->competition)<p style="margin:0;font-size:.9em">{{ $achievement->competition }}</p>@endif
            @if ($achievement->summary)<p style="margin-top:var(--space-2, 8px)">{{ $achievement->summary }}</p>@endif
            @if ($layout === 'cards')<small style="color:var(--color-muted, #6b7280)">{{ $achievement->achieved_on->translatedFormat('j F Y') }}</small>@endif
        @endif
    </div>
</article>
