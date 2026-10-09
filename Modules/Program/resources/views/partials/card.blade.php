{{-- One program in a listing or section. Contract: $program (Program), $style = card|list|detailed --}}
@php
    $image = $program->firstMedia('image');
    $facts = array_filter([
        $program->level?->name,
        $program->durationText(),
        $program->total_credits !== null ? __('program::program.credits', ['count' => $program::plainNumber($program->total_credits)]) : null,
        $program->shiftLabel(),
    ]);
@endphp
<article @class(['program-card', "program-card--{$style}"])>
    <a href="{{ $program->url() }}" class="program-card__link" style="display:block;color:inherit;text-decoration:none">
        @if ($style !== 'list' && $image)
            <img src="{{ $image->url('medium') }}" alt="{{ $image->getTranslation('alt_text', app()->getLocale(), true) }}" loading="lazy"
                 style="width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:var(--radius-md, 8px)">
        @endif
        <div class="program-card__body">
            <span class="program-card__short" style="font-size:var(--text-xs, 14px);color:var(--color-primary)">{{ $program->short_name }}@if ($program->department) · {{ $program->department->short_name }}@endif</span>
            <h3 class="program-card__title" style="font-size:var(--text-lg, 20px);margin:.25rem 0">{{ $program->name }}</h3>
            @if ($facts !== [])
                <p class="program-card__facts" style="font-size:var(--text-sm, 16px);color:var(--color-text-muted);margin:0">{{ implode(' · ', $facts) }}</p>
            @endif
            @if ($style === 'detailed' && filled($program->summary))
                <div class="program-card__text" style="margin-top:.5rem">{{ \Illuminate\Support\Str::limit(strip_tags($program->summary), 320) }}</div>
            @endif
        </div>
    </a>
</article>
