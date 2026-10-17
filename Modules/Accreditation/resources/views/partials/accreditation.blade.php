{{-- One accreditation card. Contract: $accreditation, $base --}}
@php($logo = $accreditation->firstMedia('logo'))
<article class="accreditation" style="border:1px solid var(--color-border);border-radius:var(--radius, 8px);padding:var(--space-4, 16px);display:flex;gap:var(--space-4, 16px);align-items:flex-start;height:100%">
    @if ($logo)<img src="{{ $logo->url('thumb') ?? $logo->url() }}" alt="" loading="lazy" style="width:72px;height:72px;object-fit:contain;flex:0 0 72px">@endif
    <div>
        <h3 style="margin:0 0 4px;font-size:1.05rem">
            @if ($accreditation->body)<a href="{{ url($base.'/'.\Modules\Accreditation\Models\Accreditation::PATH.'/'.$accreditation->slug()) }}">{{ $accreditation->title }}</a>@else{{ $accreditation->title }}@endif
            @if ($accreditation->isExpired())<span class="badge" style="margin-left:6px;font-size:.75rem;padding:2px 8px;border-radius:999px;background:var(--color-danger, #b91c1c);color:#fff">{{ __('accreditation::accreditation.expired') }}</span>@endif
        </h3>
        @if ($accreditation->agency)<p style="margin:0">{{ $accreditation->agency }}</p>@endif
        <p style="margin:4px 0 0;font-size:.85rem;opacity:.8">
            @if ($accreditation->awarded_on){{ __('accreditation::accreditation.awarded', ['date' => $accreditation->awarded_on->translatedFormat('j F Y')]) }}@endif
            @if ($accreditation->awarded_on && $accreditation->expires_on) · @endif
            @if ($accreditation->expires_on){{ __('accreditation::accreditation.valid_until', ['date' => $accreditation->expires_on->translatedFormat('j F Y')]) }}@endif
        </p>
        @if ($certificate = $accreditation->firstMedia('certificate'))<p style="margin:6px 0 0"><a href="{{ $certificate->url() }}" target="_blank" rel="noopener">{{ __('accreditation::accreditation.certificate') }}</a></p>@endif
    </div>
</article>
