{{-- Scholarships as cards. Contract: $scholarships (with parts_count) --}}
<div class="scholarship-grid" style="display:grid;gap:var(--space-6, 24px);grid-template-columns:repeat(auto-fill,minmax(280px,1fr));margin-top:var(--space-4, 16px)">
    @foreach ($scholarships as $scholarship)
        <article class="scholarship-card card" style="display:flex;flex-direction:column;gap:var(--space-3, 12px);padding:var(--space-6, 24px);border:1px solid var(--color-border);border-radius:var(--radius-md, 8px);background:var(--color-surface)">
            @if ($image = $scholarship->firstMedia('image')?->url('medium'))
                <img src="{{ $image }}" alt="" loading="lazy" style="width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:var(--radius-sm, 4px)">
            @endif
            <h3 style="font-size:var(--text-xl, 20px);margin:0"><a href="{{ $scholarship->url() }}">{{ $scholarship->title }}</a></h3>
            @if ($scholarship->summary)
                <p style="margin:0;color:var(--color-text-muted)">{{ $scholarship->summary }}</p>
            @endif
            @if ($scholarship->parts_count)
                <p style="margin:0;font-size:var(--text-sm, 14px)">{{ trans_choice('scholarship::scholarship.parts_count', $scholarship->parts_count, ['count' => $scholarship->parts_count]) }}</p>
            @endif
            <a href="{{ $scholarship->url() }}" class="btn" style="margin-top:auto">{{ __('scholarship::scholarship.details') }}</a>
        </article>
    @endforeach
</div>
