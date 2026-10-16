{{-- One testimonial. Contract: $testimonial, $video (bool: show the video instead of the photo) --}}
@php($person = $testimonial->person())
@php($photo = $person?->photoUrl() ?? $testimonial->firstMedia('photo')?->url('medium'))
@php($embed = ($video ?? false) ? $testimonial->videoEmbed() : null)
<figure class="testimonial-card" style="margin:0;height:100%;display:flex;flex-direction:column;gap:var(--space-3, 12px);padding:var(--space-5, 20px);border:1px solid var(--color-border);border-radius:var(--radius, 8px);background:var(--color-surface, #fff)">
    @if ($embed)
        <div style="position:relative;aspect-ratio:16/9"><iframe src="{{ $embed }}" title="{{ $person?->name() }}" loading="lazy" allowfullscreen style="position:absolute;inset:0;width:100%;height:100%;border:0;border-radius:var(--radius, 8px)"></iframe></div>
    @endif
    @if ($testimonial->rating)
        <div aria-label="{{ __('testimonials::testimonials.rated', ['rating' => $testimonial->rating]) }}" style="color:var(--color-accent, #F59E0B)">{{ str_repeat('★', $testimonial->rating) }}<span style="opacity:.3">{{ str_repeat('★', 5 - $testimonial->rating) }}</span></div>
    @endif
    <blockquote style="margin:0;flex:1;font-style:italic">“{{ $testimonial->quote }}”</blockquote>
    @if ($person)
        <figcaption style="display:flex;gap:var(--space-3, 12px);align-items:center">
            @if ($photo && ! $embed)<img src="{{ $photo }}" alt="{{ $person->name() }}" loading="lazy" style="width:56px;height:56px;border-radius:50%;object-fit:cover">@endif
            <span>
                <strong>@if ($person->profileUrl())<a href="{{ $person->profileUrl() }}">{{ $person->name() }}</a>@else{{ $person->name() }}@endif</strong>
                @if ($title = $person->assignment->role_label ?: $person->designation())<br><small>{{ $title }}</small>@endif
            </span>
        </figcaption>
    @endif
</figure>
