{{-- One event as a card (cards) or a row (list / timeline). Contract: $event, $layout, $base (place path or '') --}}
@php($href = url($base.'/'.\Modules\Events\Models\Event::PATH.'/'.$event->slug()))
@php($image = $event->firstMedia('featured_image'))
@php($state = $event->state())
<article class="event-card" style="display:flex;{{ $layout === 'cards' ? 'flex-direction:column' : 'flex-direction:row;gap:var(--space-4, 16px);align-items:flex-start' }};border:1px solid var(--color-border);border-radius:var(--radius-md, 8px);overflow:hidden;background:var(--color-surface, #fff)">
    @if ($layout === 'cards' && $image)
        <a href="{{ $href }}" style="display:block;aspect-ratio:16/9;overflow:hidden"><img src="{{ $image->url('medium') }}" alt="" loading="lazy" style="width:100%;height:100%;object-fit:cover"></a>
    @elseif ($layout !== 'cards')
        <span aria-hidden="true" style="flex:none;width:64px;text-align:center;padding:var(--space-2, 8px);background:var(--color-primary, #034EA2);color:#fff">
            <strong style="display:block;font-size:1.5em;line-height:1">{{ $event->starts_at->day }}</strong>
            <small>{{ $event->starts_at->translatedFormat('M Y') }}</small>
        </span>
    @endif
    <div style="padding:var(--space-4, 16px);display:grid;gap:var(--space-1, 4px)">
        <p style="margin:0;font-size:.85em;color:var(--color-muted, #6b7280)">
            {{ $event->when() }}
            @if ($state !== 'upcoming') <span class="badge {{ $state === 'ongoing' ? 'badge-success' : 'badge-muted' }}">{{ __("events::events.states.{$state}") }}</span>@endif
        </p>
        <h3 style="margin:0;font-size:1.1em"><a href="{{ $href }}">{{ $event->title }}</a></h3>
        @if (filled($event->venue))<p style="margin:0;font-size:.9em">{{ $event->venue }}</p>@endif
    </div>
</article>
