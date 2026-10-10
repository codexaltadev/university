{{-- Scholarships as an accordion (DIU's "Waiver and Scholarship" page). Contract: $scholarships (with parts_count) --}}
<div class="scholarship-accordion" style="display:grid;gap:var(--space-2, 8px);margin-top:var(--space-4, 16px)">
    @foreach ($scholarships as $scholarship)
        <details class="accordion__item" style="border:1px solid var(--color-border);border-radius:var(--radius-sm, 4px);padding:var(--space-3, 12px) var(--space-4, 16px)">
            <summary class="accordion__title" style="cursor:pointer;font-weight:600">{{ $scholarship->title }}</summary>
            <div class="accordion__body prose" style="margin-top:var(--space-3, 12px)">
                @if ($scholarship->summary)
                    <p>{{ $scholarship->summary }}</p>
                @endif
                {!! \Illuminate\Support\Str::sanitizeHtml((string) $scholarship->details) !!}
                @if ($scholarship->parts_count || $scholarship->eligibility || $scholarship->how_to_apply || $scholarship->apply_url)
                    <p><a href="{{ $scholarship->url() }}">{{ __('scholarship::scholarship.details') }} →</a></p>
                @endif
            </div>
        </details>
    @endforeach
</div>
