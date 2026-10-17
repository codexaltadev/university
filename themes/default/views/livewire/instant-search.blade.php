{{--
    Search (FR-SRCH-05), combobox pattern — the default theme's look (DIU search window:
    the field over a rule, results or the empty state on a light grey area below).
    Contract: wire:model "q", $results (or null), $allUrl, $scope.
--}}
<div class="instant-search flex flex-col flex-1 min-h-0">
    <form action="{{ route('search') }}" method="get" role="search" class="relative px-6 pb-6 border-b border-gray-200">
        <label for="instant-search-q" class="sr-only">{{ __('platform::search.label') }}</label>
        <x-theme::icon name="search" class="absolute left-9 top-[1.35rem] -translate-y-1/2 text-gray-400 pointer-events-none" />
        <input id="instant-search-q" type="search" name="q" autocomplete="off" maxlength="100"
               wire:model.live.debounce.300ms="q"
               role="combobox" aria-controls="instant-search-list" aria-expanded="{{ $results ? 'true' : 'false' }}"
               placeholder="{{ __('platform::search.placeholder') }}"
               class="w-full !min-h-0 pl-10 pr-4 py-2.5 text-sm rounded-xl border-2 border-blue-500/80 bg-white text-gray-900 placeholder-gray-400 outline-none focus:border-blue-600">
        @if ($scope)
            <input type="hidden" name="in" value="{{ $scope }}">
        @endif
    </form>

    <div class="flex-1 overflow-y-auto bg-gray-50/60 px-6 py-4">
        @if ($results)
            <div id="instant-search-list" role="listbox" class="divide-y divide-gray-100">
                @forelse ($results['items'] as $item)
                    <a role="option" href="{{ $item['url'] }}" class="block px-3 py-3 rounded-lg text-gray-900 hover:bg-white hover:no-underline [&_mark]:bg-yellow-200 [&_mark]:text-inherit">
                        <span class="block text-xs text-gray-500 mb-0.5">{{ $item['label'] }}</span>
                        {{ $item['title'] }}
                    </a>
                @empty
                    <p class="py-16 text-center text-gray-500">{{ __('platform::search.none') }}</p>
                @endforelse
                @if ($results['total'] > count($results['items']->items()))
                    <a href="{{ $allUrl }}" class="block px-3 py-3 font-semibold text-blue-600">{{ __('platform::search.all_results', ['count' => $results['total']]) }}</a>
                @endif
            </div>
        @else
            <div class="py-16 text-center">
                <x-theme::icon name="search" width="56" height="56" class="mx-auto text-gray-300" />
                <p class="mt-4 text-lg font-medium text-gray-600">{{ __('theme::theme.search_start') }}</p>
                <p class="mt-1 text-gray-400 max-w-md mx-auto">{{ __('theme::theme.search_hint') }}</p>
            </div>
        @endif
    </div>
</div>
