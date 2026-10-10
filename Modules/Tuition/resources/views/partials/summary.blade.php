{{-- One tuition's figures (FR-FEE-02/04). Contract: $tuition (Tuition with lines, category), $detailed (bool: list every line) --}}
@php
    $c = $tuition->category;
    $range = fn ($min, $max) => $max !== null && (float) $max > (float) $min ? $c->money($min).' – '.$c->money($max) : $c->money($min);
@endphp
<dl class="tuition-summary" style="display:grid;grid-template-columns:minmax(0, 1fr) auto;gap:.25rem 1rem;margin:0">
    @if ($tuition->creditsText()) <dt>{{ __('tuition::tuition.fields.credits') }}</dt><dd>{{ $tuition->creditsText() }}</dd> @endif
    @if (filled($tuition->duration_label)) <dt>{{ __('tuition::tuition.fields.duration') }}</dt><dd>{{ $tuition->duration_label }}</dd> @endif
    @if ($detailed)
        @foreach ($tuition->lines->where('is_active', true) as $line)
            <dt>{{ $line->label }}</dt>
            <dd>{{ $range($line->amount, $line->amount_max) }}@if ($line->line_type === 'term') <small>/{{ __('tuition::tuition.per_semester') }}</small>@elseif ($line->line_type === 'per_credit') <small>/{{ __('tuition::tuition.per_credit') }}</small>@endif</dd>
        @endforeach
    @endif
    @if ($tuition->tuition_min !== null) <dt>{{ __('tuition::tuition.fields.tuition_total') }}</dt><dd>{{ $range($tuition->tuition_min, $tuition->tuition_max) }}</dd> @endif
    @if ($tuition->total_min !== null) <dt><strong>{{ __('tuition::tuition.fields.total') }}</strong></dt><dd><strong>{{ $range($tuition->total_min, $tuition->total_max) }}</strong></dd> @endif
</dl>
@if ($tuition->prospectus_url)
    <p style="margin-top:.5rem"><a href="{{ $tuition->prospectus_url }}" target="_blank" rel="noopener">{{ __('tuition::tuition.prospectus') }}</a></p>
@endif
