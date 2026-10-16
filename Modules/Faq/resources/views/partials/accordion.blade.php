{{-- FAQs as an accordion. Contract: $faqs --}}
<div class="faq-accordion" style="display:grid;gap:var(--space-2, 8px)">
    @foreach ($faqs as $faq)
        <details id="faq-{{ $faq->id }}" class="accordion__item" style="border:1px solid var(--color-border);border-radius:var(--radius-sm, 4px);padding:var(--space-3, 12px) var(--space-4, 16px)">
            <summary class="accordion__title" style="cursor:pointer;font-weight:600">{{ $faq->question }}</summary>
            <div class="accordion__body prose" style="margin-top:var(--space-3, 12px)">{!! \Illuminate\Support\Str::sanitizeHtml((string) $faq->answer) !!}</div>
        </details>
    @endforeach
</div>
