{{--
    /downloads as DIU's "Digital Brochures" / "Program Prospectus" (DIUFronend flipbook/brochure, prospectus): a
    white band with the title and a line; the kinds (and categories) as pills; a card per document —
    thumbnail with its kind, title, details, "View Flipbook" / "View PDF" and a download.
    Contract: the module's (documents, types, type, categories, category, year, years, q, layout, base), $page (DocumentsPage)
--}}
<x-theme::layout :title="settings('documents.title') ?: __('documents::documents.plural')">
    <div class="min-h-screen bg-gray-50">
        <div class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 text-center">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 mb-4">{{ settings('documents.title') ?: __('documents::documents.plural') }}</h1>
                @if (filled(settings('documents.text')))<p class="text-xl text-gray-600 max-w-2xl mx-auto">{{ settings('documents.text') }}</p>@endif
            </div>
        </div>

        @if (($types = $page->types()) !== [] || ($categories = $page->categories()) !== [])
            <div class="bg-white border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-3">
                    @if ($types !== [])
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-sm font-medium text-gray-900">{{ __('documents::documents.page.kind') }}</span>
                            @foreach ($types as $item)
                                <a href="{{ $item['url'] }}" @class(['px-6 py-2.5 rounded-full text-sm font-medium transition-colors hover:no-underline', 'bg-gray-900 text-white' => $item['current'], 'bg-gray-100 text-gray-700 hover:bg-gray-200' => ! $item['current']])>{{ $item['label'] }}</a>
                            @endforeach
                        </div>
                    @endif
                    @if (($categories = $page->categories()) !== [])
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-sm font-medium text-gray-900">{{ __('documents::documents.page.category') }}</span>
                            @foreach ($categories as $item)
                                <a href="{{ $item['current'] ? $page->url(['category' => null]) : $item['url'] }}" @class(['px-4 py-1.5 rounded-full text-sm font-medium transition-colors hover:no-underline', 'bg-gray-900 text-white' => $item['current'], 'bg-gray-100 text-gray-700 hover:bg-gray-200' => ! $item['current']])>{{ $item['label'] }}</a>
                            @endforeach
                        </div>
                    @endif
                    <form method="get" action="{{ $page->listingUrl() }}" class="flex gap-2 max-w-md" role="search">
                        @if ($type)<input type="hidden" name="type" value="{{ $type }}">@endif
                        @if ($category)<input type="hidden" name="category" value="{{ $category->slug() }}">@endif
                        <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('documents::documents.search_placeholder') }}" aria-label="{{ __('documents::documents.search') }}" class="flex-1 h-10 px-4 text-sm rounded-lg border border-gray-200 outline-none focus:border-gray-900">
                        <button type="submit" class="h-10 px-4 rounded-lg bg-gray-900 text-white text-sm font-medium cursor-pointer">{{ __('documents::documents.search') }}</button>
                    </form>
                </div>
            </div>
        @endif

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
            @if (($cards = $page->cards()) === [])
                <p class="text-center text-gray-500 py-12">{{ __('documents::documents.none') }}</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach ($cards as $card)
                        <div class="document-item--{{ $layout }} group bg-white rounded-xl overflow-hidden border border-gray-200 hover:border-gray-300 transition-all duration-300 hover:shadow-lg flex flex-col">
                            <a href="{{ $card['url'] }}" class="relative h-80 bg-gray-100 overflow-hidden block">
                                @if ($card['thumb'])
                                    <img src="{{ $card['thumb'] }}" alt="{{ $card['title'] }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                @else
                                    <span class="w-full h-full flex flex-col items-center justify-center gap-3 bg-gradient-to-br from-secondary to-primary text-white p-6 text-center">
                                        <x-theme::icon name="file-text" class="w-14 h-14 opacity-80" />
                                        <span class="font-semibold line-clamp-3">{{ $card['title'] }}</span>
                                    </span>
                                @endif
                                <span class="absolute top-4 right-4 inline-block px-3 py-1 bg-white text-gray-900 text-xs font-semibold rounded-full border border-gray-200">{{ $card['type'] }}</span>
                            </a>
                            <div class="p-6 flex-1 flex flex-col">
                                <h2 class="font-bold text-xl text-gray-900 mb-2 line-clamp-2 min-h-[3.5rem]"><a href="{{ $card['url'] }}" class="text-inherit hover:no-underline">{{ $card['title'] }}</a></h2>
                                @if ($card['meta'])<p class="text-sm text-gray-500 mb-4 line-clamp-2">{{ $card['meta'] }}</p>@endif
                                <div class="flex gap-3 mt-auto">
                                    @if ($card['external'])
                                        <a href="{{ $card['external'] }}" target="_blank" rel="noopener noreferrer" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors hover:no-underline"><x-theme::icon name="external" class="w-4 h-4" />{{ __('documents::documents.page.open') }}</a>
                                    @else
                                        <a href="{{ $card['url'] }}" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors hover:no-underline">
                                            <x-theme::icon name="eye" class="w-4 h-4" />{{ ! $card['readable'] ? __('documents::documents.page.details') : ($card['flipbook'] ? __('documents::documents.page.view_flipbook') : __('documents::documents.page.view_pdf')) }}
                                        </a>
                                    @endif
                                    @if ($card['download'])
                                        <a href="{{ $card['download'] }}" class="flex items-center justify-center px-4 py-3 bg-gray-100 text-gray-900 rounded-lg hover:bg-gray-200 transition-colors" title="{{ __('documents::documents.download') }}" aria-label="{{ __('documents::documents.download') }}: {{ $card['title'] }}"><x-theme::icon name="download" class="w-4 h-4" /></a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                {{ $documents->links('theme::partials.diu.pagination') }}
            @endif
        </div>
    </div>
</x-theme::layout>
