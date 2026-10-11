{{-- How to apply block (FR-ADM-01). Contract: $data, $locale --}}
@php($steps = \Modules\Admission\Models\AdmissionStep::query()->frontendVisible()->ordered()->get())
<ol class="block block--admission-steps" style="list-style:none;padding:0;display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fit,minmax(200px,1fr));counter-reset:step">
    @foreach ($steps as $step)
        <li style="padding:var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius-md, 8px)">
            <span style="display:inline-block;font-weight:700;color:var(--color-primary)">{{ $loop->iteration }}</span>
            <h3 style="margin:var(--space-2, 8px) 0">{{ $step->title }}</h3>
            @if ($step->description)
                <p style="margin:0;color:var(--color-text-muted)">{{ $step->description }}</p>
            @endif
        </li>
    @endforeach
</ol>
