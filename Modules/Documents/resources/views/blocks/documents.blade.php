{{-- Page block "Documents": a list, or one PDF as its flipbook. Contract: $data (documents, display), $locale --}}
@php($documents = \Modules\Documents\Blocks\DocumentBlock::documents($data))
@if ($documents->isNotEmpty())
    @php($first = $documents->first())
    @if (($data['display'] ?? 'list') === 'flipbook' && $first->isPdf() && ($reader = \Modules\Documents\Support\DocumentsWebsite::readerUrl($first)))
        @include('documents::partials.flipbook', ['src' => $reader, 'title' => $first->title, 'download' => $first->allow_download ? url($first->url().'/file') : null])
    @else
        <div class="block-documents">
            @foreach ($documents as $document)
                @include('documents::partials.item', ['document' => $document, 'base' => '', 'layout' => 'list'])
            @endforeach
        </div>
    @endif
@endif
