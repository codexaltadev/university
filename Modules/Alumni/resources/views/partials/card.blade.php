{{-- One alumnus as a card (grid / cards) or a row (list). Contract: $alumnus, $layout --}}
@php($photo = $alumnus->firstMedia('photo')?->url('medium'))
<article class="alumni-card" style="display:flex;{{ $layout === 'list' ? 'flex-direction:row;align-items:center' : 'flex-direction:column;align-items:center;text-align:center' }};gap:var(--space-3, 12px);padding:var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius-md, 8px)">
    @if ($photo)
        <img src="{{ $photo }}" alt="" loading="lazy" style="width:{{ $layout === 'list' ? '72px' : '112px' }};height:{{ $layout === 'list' ? '72px' : '112px' }};border-radius:50%;object-fit:cover">
    @else
        <span aria-hidden="true" style="display:grid;place-items:center;width:{{ $layout === 'list' ? '72px' : '112px' }};height:{{ $layout === 'list' ? '72px' : '112px' }};border-radius:50%;background:var(--color-surface, #F3F4F9);font-weight:700;font-size:1.6em">{{ mb_substr((string) $alumnus->name, 0, 1) }}</span>
    @endif
    <div>
        <h3 style="margin:0;font-size:1.05em"><a href="{{ url($alumnus->url()) }}">{{ $alumnus->name }}</a>@if ($alumnus->is_pride) <span class="badge badge--success">{{ __('alumni::alumni.pride_badge') }}</span>@endif</h3>
        @if ($headline = $alumnus->headline())<p style="margin:2px 0">{{ $headline }}</p>@endif
        <p style="margin:2px 0;color:var(--color-muted, #6b7280);font-size:.9em">
            {{ collect([$alumnus->batch ? __('alumni::alumni.batch_n', ['batch' => $alumnus->batch]) : null, $alumnus->passing_year])->filter()->implode(' · ') }}
        </p>
        @if ($layout === 'cards' && filled($alumnus->story))
            <p style="margin:6px 0 0">{{ \Illuminate\Support\Str::words(strip_tags((string) $alumnus->story), 25) }}</p>
        @endif
    </div>
</article>
