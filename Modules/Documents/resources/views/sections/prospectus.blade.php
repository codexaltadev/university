{{-- Homepage section "Prospectus" (FR-DOC-08): covers that open the flipbook. Contract: $items (Document list), $section, $settings; $context from the page --}}
@if ($items->isNotEmpty())
    @php($base = isset($context) && $context->isMicrosite() ? rtrim((string) app(\Platform\Context\MicrositeResolver::class)->urlFor($context->entity), '/') : '')
    <div style="display:grid;gap:var(--space-5, 20px);grid-template-columns:repeat(auto-fill,minmax(200px,1fr))">
        @foreach ($items as $document)
            @include('documents::partials.item', ['document' => $document, 'base' => $base, 'layout' => 'cards'])
        @endforeach
    </div>
@endif
