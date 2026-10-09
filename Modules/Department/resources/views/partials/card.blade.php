{{-- One department in a listing (FR-DEPT-04: its own website when it has one). Contract: $department (Department), $style = grid|list|card --}}
@php
    $photo = $department->firstMedia('photo') ?? $department->firstMedia('cover');
    $external = filled($department->external_url);
@endphp
<article @class(['department-card', "department-card--{$style}"])>
    <a href="{{ $department->listingUrl() }}" class="department-card__link" style="display:block;color:inherit;text-decoration:none"
       @if ($external) target="_blank" rel="noopener" @endif>
        @if ($style !== 'list' && $photo)
            <img src="{{ $photo->url('medium') }}" alt="{{ $photo->getTranslation('alt_text', app()->getLocale(), true) }}" loading="lazy"
                 style="width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:var(--radius-md, 8px)">
        @endif
        <div class="department-card__body">
            <span class="department-card__short" style="font-size:var(--text-xs, 14px);color:var(--color-primary)">{{ $department->short_name }}</span>
            <h3 class="department-card__title" style="font-size:var(--text-lg, 20px);margin:.25rem 0">{{ $department->name }}</h3>
            @if ($style !== 'grid' && filled($department->about))
                <p class="department-card__text" style="color:var(--color-text-muted);margin:0">{{ \Illuminate\Support\Str::limit(strip_tags($department->about), 180) }}</p>
            @endif
            @if ($external)
                <span class="department-card__external" style="font-size:var(--text-xs, 14px)">{{ __('department::department.visit_website') }} ↗</span>
            @endif
        </div>
    </a>
</article>
