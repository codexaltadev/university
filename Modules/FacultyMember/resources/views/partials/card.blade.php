{{-- One teacher as a card (grid / cards) or a row (list). Contract: $member (with designation, department), $layout (grid / list / cards) --}}
@php($person = $member->personPresenter())
@php($row = $layout === 'list')
@php($size = $row ? '64px' : ($layout === 'cards' ? '120px' : '96px'))
<article class="person-card faculty-member-card" style="display:flex;{{ $row ? 'flex-direction:row;align-items:center' : 'flex-direction:column' }};gap:var(--space-3, 12px);padding:var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius-md, 8px)">
    @if ($photo = $person->photoUrl())
        <img src="{{ $photo }}" alt="" loading="lazy" style="width:{{ $size }};height:{{ $size }};border-radius:{{ $layout === 'cards' ? 'var(--radius-md, 8px)' : '50%' }};object-fit:cover">
    @else
        <span aria-hidden="true" style="display:grid;place-items:center;width:{{ $size }};height:{{ $size }};border-radius:50%;background:var(--color-surface, #F3F4F9);font-weight:700;font-size:1.4em">{{ mb_substr($person->name(), 0, 1) }}</span>
    @endif
    <div>
        <h3 style="margin:0;font-size:1.05em">@if ($url = $person->profileUrl())<a href="{{ $url }}">{{ $person->name() }}</a>@else{{ $person->name() }}@endif</h3>
        @if ($person->designation())<p style="margin:2px 0">{{ $person->designation() }}</p>@endif
        @if ($member->department)<p style="margin:2px 0;color:var(--color-muted, #6b7280)">{{ $member->department->short_name ?: $member->department->name }}</p>@endif
        @if ($member->isOnLeave())<p style="margin:2px 0"><span class="badge">{{ __('facultymember::facultymember.employment.on_leave') }}</span></p>@endif
        <p style="margin:4px 0 0;font-size:.9em">
            @if ($email = $person->protectedEmailLink()){{ $email }}@endif
            @if ($layout !== 'grid' && ($phone = $person->phone()))<br><a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">{{ $phone }}</a>@if ($person->extension()) · {{ __('facultymember::facultymember.ext') }} {{ $person->extension() }}@endif @endif
        </p>
    </div>
</article>
