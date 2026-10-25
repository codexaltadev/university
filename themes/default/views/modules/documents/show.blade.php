{{--
    /downloads/{slug}: DIU shows a document as a full-page flipbook (flipbook/brochure/[slug]); here the same book
    under a white band with its kind, title and details, the download, a newer version's note and the
    older versions. Contract: $document, $reader, $download, $older, $base, $page (DocumentsPage)
--}}
<x-theme::layout :title="$document->title">
    <div class="min-h-screen bg-gray-50">
        <div class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <a href="{{ $page->listingUrl() }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-900 hover:no-underline"><x-theme::icon name="arrow-left" class="w-4 h-4" />{{ settings('documents.title') ?: __('documents::documents.plural') }}</a>
                <div class="mt-4 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                    <div>
                        <span class="inline-block px-3 py-1 bg-gray-100 text-gray-900 text-xs font-semibold rounded-full">{{ __("documents::documents.types.{$document->document_type}") }}</span>
                        <h1 class="mt-3 text-3xl sm:text-4xl font-bold text-gray-900">{{ $document->title }}</h1>
                        @if ($document->description)<p class="mt-3 text-lg text-gray-600 max-w-3xl">{{ $document->description }}</p>@endif
                    </div>
                    <div class="flex gap-3 shrink-0">
                        @if ($download)<a href="{{ $download }}" class="flex items-center justify-center gap-2 px-5 py-3 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 hover:no-underline"><x-theme::icon name="download" class="w-4 h-4" />{{ __('documents::documents.page.download_pdf') }}</a>@endif
                        @if (! $reader && $document->external_url)<a href="{{ $document->external_url }}" target="_blank" rel="noopener noreferrer" class="flex items-center justify-center gap-2 px-5 py-3 bg-gray-900 text-white text-sm font-medium rounded-lg hover:no-underline"><x-theme::icon name="external" class="w-4 h-4" />{{ __('documents::documents.page.open') }}</a>@endif
                    </div>
                </div>
                @if (($facts = $page->facts()) !== [])
                    <dl class="mt-6 flex flex-wrap gap-x-8 gap-y-2 text-sm">
                        @foreach ($facts as $fact)
                            <div><dt class="inline text-gray-500">{{ $fact['label'] }}:</dt> <dd class="inline font-medium text-gray-900">@if ($fact['url'])<a href="{{ $fact['url'] }}" class="text-primary">{{ $fact['value'] }}</a>@else{{ $fact['value'] }}@endif</dd></div>
                        @endforeach
                    </dl>
                @endif
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
            @if ($document->supersededBy && $document->supersededBy->isFrontendVisible())
                <p role="note" class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900">{{ __('documents::documents.superseded_note') }} <a href="{{ url($document->supersededBy->url()) }}" class="font-semibold underline">{{ $document->supersededBy->title }}@if ($document->supersededBy->version_label) ({{ $document->supersededBy->version_label }})@endif</a></p>
            @endif

            @if ($reader && $document->flipbook())
                <div class="bg-white rounded-xl border border-gray-200 p-4">@include('documents::partials.flipbook', ['src' => $reader, 'title' => $document->title, 'download' => $download])</div>
            @elseif ($reader)
                <iframe src="{{ $reader }}#view=FitH" title="{{ $document->title }}" loading="lazy" class="w-full h-[85vh] bg-white rounded-xl border border-gray-200"></iframe>
            @endif

            @if ($older->isNotEmpty())
                <section class="bg-white rounded-xl border border-gray-200 p-6" aria-labelledby="doc-older">
                    <h2 id="doc-older" class="text-lg font-bold text-gray-900 mb-3">{{ __('documents::documents.older_versions') }}</h2>
                    <ul class="divide-y divide-gray-100">
                        @foreach ($older as $old)
                            <li class="py-2"><a href="{{ url($old->url()) }}" class="text-primary font-medium">{{ $old->version_label ?: $old->title }}</a>@if ($old->approved_on)<span class="text-gray-500"> — {{ $old->approved_on->translatedFormat('j F Y') }}</span>@endif</li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>
</x-theme::layout>
