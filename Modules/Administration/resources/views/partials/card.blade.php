{{-- One member as a card (grid) or a row (list). Contract: $member, $office (name or null), $row (bool) --}}
@php($person = $member->personPresenter())
<article class="person-card" style="display:flex;{{ $row ? 'flex-direction:row;align-items:center' : 'flex-direction:column' }};gap:var(--space-3, 12px);padding:var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius-md, 8px)">
    @if ($photo = $person->photoUrl())
        <img src="{{ $photo }}" alt="" loading="lazy" style="width:{{ $row ? '64px' : '96px' }};height:{{ $row ? '64px' : '96px' }};border-radius:50%;object-fit:cover">
    @else
        <span aria-hidden="true" style="display:grid;place-items:center;width:{{ $row ? '64px' : '96px' }};height:{{ $row ? '64px' : '96px' }};border-radius:50%;background:var(--color-surface, #F3F4F9);font-weight:700;font-size:1.4em">{{ mb_substr($person->name(), 0, 1) }}</span>
    @endif
    <div>
        <h3 style="margin:0;font-size:1.05em">@if ($url = $person->profileUrl())<a href="{{ $url }}">{{ $person->name() }}</a>@else{{ $person->name() }}@endif</h3>
        @if ($person->designation())<p style="margin:2px 0">{{ $person->designation() }}</p>@endif
        @if ($office)<p style="margin:2px 0;color:var(--color-muted, #6b7280)">{{ $office }}</p>@endif
        <p style="margin:4px 0 0;font-size:.9em">
            @if ($phone = $person->phone())<a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">{{ $phone }}</a>@endif
            @if ($person->extension()) · {{ __('administration::administration.ext') }} {{ $person->extension() }}@endif
            @if ($email = $person->email())<br><a href="mailto:{{ $email }}">{{ $email }}</a>@endif
        </p>
    </div>
</article>
