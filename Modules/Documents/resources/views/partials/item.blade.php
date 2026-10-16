{{-- One document in a list (cards / list). Contract: $document, $base (microsite path or ''), $layout (cards / list) --}}
@php($href = url($base !== '' ? $base.'/'.\Modules\Documents\Models\Document::PATH.'/'.$document->slug() : $document->url()))
@php($cover = $document->firstMedia('thumbnail'))
<article class="document-item document-item--{{ $layout }}" style="{{ $layout === 'list' ? 'display:flex;gap:var(--space-4, 16px);align-items:center;padding:var(--space-3, 12px) 0;border-bottom:1px solid var(--color-border)' : 'height:100%;border:1px solid var(--color-border);border-radius:var(--radius, 8px);padding:var(--space-4, 16px)' }}">
    @if ($cover && $layout === 'cards')
        <a href="{{ $href }}"><img src="{{ $cover->url('medium') ?? $cover->url() }}" alt="" loading="lazy" style="width:100%;aspect-ratio:1/1.3;object-fit:cover;border-radius:var(--radius-sm, 4px);margin-bottom:var(--space-3, 12px)"></a>
    @endif
    <div style="flex:1">
        <small style="text-transform:uppercase;letter-spacing:.04em;color:var(--color-muted, #6b7280)">{{ __("documents::documents.types.{$document->document_type}") }}@if ($document->version_label) · {{ $document->version_label }}@endif @if ($document->session_label) · {{ $document->session_label }}@endif</small>
        <h3 style="margin:var(--space-1, 4px) 0;font-size:1rem"><a href="{{ $href }}">{{ $document->title }}</a></h3>
        @if ($facts = $document->fileFacts())<small style="color:var(--color-muted, #6b7280)">{{ $facts }}</small>@endif
    </div>
    <p style="margin:var(--space-2, 8px) 0 0;display:flex;gap:var(--space-2, 8px);flex-wrap:wrap">
        @if ($document->flipbook())<a class="button button--outline" href="{{ $href }}">{{ __('documents::documents.read') }}</a>@endif
        @if ($document->allow_download && ($document->fileItem() || $document->external_url))<a class="button" href="{{ $href }}/file">{{ __('documents::documents.download') }}</a>@endif
    </p>
</article>
