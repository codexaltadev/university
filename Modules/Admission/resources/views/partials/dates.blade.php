{{-- A circular's important dates (FR-ADM-02). Contract: $circular --}}
@if ($dates = $circular->importantDates())
    <dl class="admission-dates" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:var(--space-3, 12px);margin:var(--space-4, 16px) 0">
        @foreach ($dates as $key => $date)
            <div style="padding:var(--space-3, 12px);border:1px solid var(--color-border);border-radius:var(--radius-sm, 4px);{{ $date->endOfDay()->isPast() ? 'opacity:.65' : '' }}">
                <dt style="font-size:var(--text-sm, 14px);color:var(--color-text-muted)">{{ __("admission::admission.dates.{$key}") }}</dt>
                <dd style="margin:0;font-weight:600"><time datetime="{{ $date->toDateString() }}">{{ $date->translatedFormat('d F Y') }}</time></dd>
            </div>
        @endforeach
    </dl>
@endif
