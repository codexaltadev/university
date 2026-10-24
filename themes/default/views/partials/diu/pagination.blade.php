{{-- Page numbers for any listing: $items->links('theme::partials.diu.pagination'). Contract: $paginator, $elements (Laravel's) --}}
@if ($paginator->hasPages())
    <nav class="flex items-center justify-center gap-2 mt-12" aria-label="{{ __('theme::theme.pagination') }}">
        @if ($paginator->onFirstPage())
            <span class="px-4 h-10 inline-flex items-center rounded-lg border border-gray-200 text-gray-300" aria-disabled="true"><x-theme::icon name="chevron-left" class="w-4 h-4" /></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="px-4 h-10 inline-flex items-center rounded-lg border border-gray-200 text-gray-700 hover:border-gray-900 hover:no-underline" aria-label="{{ __('pagination.previous') }}"><x-theme::icon name="chevron-left" class="w-4 h-4" /></a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-2 text-gray-400">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $number => $link)
                    @if ($number === $paginator->currentPage())
                        <span aria-current="page" class="min-w-10 h-10 px-3 inline-flex items-center justify-center rounded-lg bg-primary text-white font-semibold">{{ $number }}</span>
                    @else
                        <a href="{{ $link }}" class="min-w-10 h-10 px-3 inline-flex items-center justify-center rounded-lg border border-gray-200 text-gray-700 hover:border-gray-900 hover:no-underline">{{ $number }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="px-4 h-10 inline-flex items-center rounded-lg border border-gray-200 text-gray-700 hover:border-gray-900 hover:no-underline" aria-label="{{ __('pagination.next') }}"><x-theme::icon name="chevron-right" class="w-4 h-4" /></a>
        @else
            <span class="px-4 h-10 inline-flex items-center rounded-lg border border-gray-200 text-gray-300" aria-disabled="true"><x-theme::icon name="chevron-right" class="w-4 h-4" /></span>
        @endif
    </nav>
@endif
