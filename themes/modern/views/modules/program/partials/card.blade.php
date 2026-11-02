{{-- One program in a listing or section (modern card). Contract: $program (Program), $style = card|list|detailed --}}
<article @class(['card card--link', 'card--media' => $style !== 'list', 'program-card', "program-card--{$style}"])>
    @if ($style !== 'list')
        <div class="card__media">@if ($image = $program->firstMedia('image'))<img src="{{ $image->url('medium') }}" alt="" loading="lazy">@endif</div>
    @endif
    <div @class(['card__body' => $style !== 'list'])>
        <span class="flex flex-wrap gap-2">
            @if ($program->level)<span class="badge">{{ $program->level->name }}</span>@endif
            @if ($program->short_name)<span class="badge badge--muted">{{ $program->short_name }}</span>@endif
        </span>
        <h3 class="card__title mt-2"><a href="{{ $program->url() }}" class="stretched">{{ $program->name }}</a></h3>
        @if ($style === 'detailed' && filled($program->summary))
            <p class="muted line-clamp-3">{{ str(strip_tags((string) $program->summary))->limit(260) }}</p>
        @endif
        <p class="card__meta">{{ collect([$program->durationText(), $program->total_credits !== null ? __('program::program.credits', ['count' => $program::plainNumber($program->total_credits)]) : null, $program->department?->name])->filter()->implode(' · ') }}</p>
    </div>
</article>
