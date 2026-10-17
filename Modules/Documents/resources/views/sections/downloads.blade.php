{{-- Homepage section "Downloads" (FR-DOC-08). Contract: $items (Document list), $section, $settings (types); $context from the page --}}
@if ($items->isNotEmpty())
    @php($base = isset($context) && $context->isMicrosite() ? rtrim((string) app(\Platform\Context\MicrositeResolver::class)->urlFor($context->entity), '/') : '')
    <div>
        @foreach ($items as $document)
            @include('documents::partials.item', ['document' => $document, 'base' => $base, 'layout' => 'list'])
        @endforeach
    </div>
    <p style="margin-top:var(--space-4, 16px)"><a href="{{ url($base.'/'.\Modules\Documents\Models\Document::PATH) }}" class="btn">{{ __('documents::documents.all_documents') }}</a></p>
@endif
