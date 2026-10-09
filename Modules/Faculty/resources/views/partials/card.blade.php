{{-- One faculty in a listing. Contract: $faculty (Faculty), $style = grid|list|card --}}
@php($photo = $faculty->firstMedia('photo') ?? $faculty->firstMedia('cover'))
<article @class(['faculty-card', "faculty-card--{$style}"])>
    <a href="{{ $faculty->url() }}" class="faculty-card__link" style="display:block;color:inherit;text-decoration:none">
        @if ($style !== 'list' && $photo)
            <img src="{{ $photo->url('medium') }}" alt="{{ $photo->getTranslation('alt_text', app()->getLocale(), true) }}" loading="lazy"
                 style="width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:var(--radius-md, 8px)">
        @endif
        <div class="faculty-card__body">
            <span class="faculty-card__short" style="font-size:var(--text-xs, 14px);color:var(--color-primary)">{{ $faculty->short_name }}</span>
            <h2 class="faculty-card__title" style="font-size:var(--text-lg, 20px);margin:.25rem 0">{{ $faculty->name }}</h2>
            @if ($style !== 'grid' && filled($faculty->overview))
                <p class="faculty-card__text" style="color:var(--color-text-muted);margin:0">{{ \Illuminate\Support\Str::limit(strip_tags($faculty->overview), 180) }}</p>
            @endif
        </div>
    </a>
</article>
