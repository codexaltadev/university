{{-- Program page tab "Tuition Fees" (FR-FEE-04). Contract: $entity (Program), $tuitions (Collection<Tuition>) --}}
<div class="program-tuition" style="display:grid;gap:var(--space-6, 24px);grid-template-columns:repeat(auto-fit, minmax(280px, 1fr))">
    @foreach ($tuitions as $tuition)
        <div class="program-tuition__item" style="border:1px solid var(--color-border);border-radius:var(--radius-md, 8px);padding:var(--space-4, 16px)">
            <h3 style="margin-top:0">{{ $tuition->category->name }}@if (filled($tuition->label)) — {{ $tuition->label }}@endif</h3>
            @include('fee::partials.tuition-summary', ['tuition' => $tuition, 'detailed' => true])
        </div>
    @endforeach
</div>
<p style="margin-top:var(--space-4, 16px)"><a href="{{ url('/tuition-fees') }}">{{ __('fee::fee.all_tuition_fees') }}</a></p>
