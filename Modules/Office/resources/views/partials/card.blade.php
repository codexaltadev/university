{{-- One office: a link to its microsite, or a contact card when it has none (FR-OFF-11). Contract: $office --}}
<article class="office-card card" style="display:flex;gap:var(--space-3, 12px);padding:var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius-md, 8px);background:var(--color-surface)">
    @if ($logo = $office->firstMedia('logo')?->url('thumb'))
        <img src="{{ $logo }}" alt="" loading="lazy" width="56" height="56" style="width:56px;height:56px;object-fit:contain">
    @endif
    <div style="display:grid;gap:.25rem">
        <h3 style="margin:0;font-size:var(--text-lg, 18px)">
            @if ($url = $office->url())<a href="{{ $url }}">{{ $office->name }}</a>@else{{ $office->name }}@endif
        </h3>
        @if (filled($office->location))<span style="color:var(--color-text-muted)">{{ $office->location }}</span>@endif
        @if ($office->phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $office->phone) }}">{{ $office->phone }}@if ($office->extension) ({{ __('office::office.ext') }} {{ $office->extension }})@endif</a>@endif
        @if ($office->email)<a href="mailto:{{ $office->email }}">{{ $office->email }}</a>@endif
    </div>
</article>
