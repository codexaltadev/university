{{--
    Search (FR-SRCH-05), combobox pattern — the default theme's look (DIU SearchModal: title and
    field over a rule, then the result count and results, or the empty state, on light grey).
    Contract: wire:model "q", $results (or null), $allUrl, $scope.
--}}
<div class="instant-search flex flex-col flex-1 min-h-0">
    <div class="flex-shrink-0 p-6 border-b border-gray-200 bg-white">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-gray-900">{{ __('theme::theme.search') }}</h2>
            <button type="button" class="p-1.5 hover:bg-gray-100 rounded-md transition-colors cursor-pointer" x-on:click="search = false">
                <x-theme::icon name="close" class="w-5 h-5 text-gray-500" />
                <span class="sr-only">{{ __('theme::theme.close') }}</span>
            </button>
        </div>
        <form action="{{ route('search') }}" method="get" role="search" class="relative">
            <label for="instant-search-q" class="sr-only">{{ __('platform::search.label') }}</label>
            <x-theme::icon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" />
            <input id="instant-search-q" type="search" name="q" autocomplete="off" maxlength="100"
                   wire:model.live.debounce.300ms="q"
                   role="combobox" aria-controls="instant-search-list" aria-expanded="{{ $results ? 'true' : 'false' }}"
                   placeholder="{{ __('theme::theme.search_placeholder') }}"
                   class="w-full !min-h-0 pl-10 pr-10 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm">
            <div wire:loading class="absolute right-3 top-1/2 -translate-y-1/2">
                <div class="animate-spin rounded-full h-4 w-4 border-2 border-gray-300 border-t-blue-600"></div>
            </div>
            @if ($scope)
                <input type="hidden" name="in" value="{{ $scope }}">
            @endif
        </form>
    </div>

    <div class="flex-1 overflow-y-auto p-6 bg-gray-50/30">
        @if ($results && $results['total'] > 0)
            <div class="space-y-4">
                <div class="p-4 border-b border-gray-200">
                    <div class="text-sm text-gray-600">
                        {{ __('theme::theme.search_found') }} <span class="font-semibold text-blue-600">{{ $results['total'] }}</span>
                        {{ trans_choice('theme::theme.search_results', $results['total']) }} "<span class="font-medium">{{ $q }}</span>"
                    </div>
                </div>
                <div id="instant-search-list" role="listbox" class="space-y-1">
                    @foreach ($results['items'] as $item)
                        <a role="option" href="{{ $item['url'] }}" class="block bg-white border-b border-gray-200 px-6 py-4 transition-all duration-200 group hover:bg-gray-50 hover:no-underline [&_mark]:bg-yellow-200 [&_mark]:text-inherit">
                            <span class="block text-lg font-semibold text-blue-600 group-hover:text-blue-800 mb-2">{{ $item['title'] }}</span>
                            <span class="inline-block bg-blue-100 text-blue-800 text-xs px-3 py-1 rounded-full font-medium">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
                @if ($results['total'] > count($results['items']->items()))
                    <a href="{{ $allUrl }}" class="block px-6 py-3 text-sm font-semibold text-blue-600 hover:text-blue-800">{{ __('platform::search.all_results', ['count' => $results['total']]) }}</a>
                @endif
            </div>
        @elseif ($results)
            <div class="text-center py-16">
                <div class="max-w-md mx-auto">
                    <div class="text-gray-500 text-lg font-medium mb-2">{{ __('theme::theme.search_none') }}</div>
                    <div class="text-gray-400 mb-2">{{ __('theme::theme.search_for', ['q' => $q]) }}</div>
                    <div class="text-gray-400 text-sm">{{ __('theme::theme.search_try') }}</div>
                </div>
            </div>
        @else
            <div class="text-center py-16">
                <div class="max-w-lg mx-auto">
                    <x-theme::icon name="search" width="64" height="64" class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                    <div class="text-gray-500 text-lg font-medium mb-2">{{ __('theme::theme.search_start') }}</div>
                    <div class="text-gray-400">{{ __('theme::theme.search_hint') }}</div>
                </div>
            </div>
        @endif
    </div>
</div>
