{{--
    Search (FR-SRCH-05), combobox pattern — the default theme's look (DIU SearchModal: title and
    field over a rule, then the result count and results, or the empty state, on light grey).
    Better than DIU's: each result shows the text around the words, up / down and Enter move through
    the results, the visitor's last searches (kept in the browser) and the popular searches show before typing.
    Contract: wire:model "q", $results (or null), $allUrl, $scope, $shortcuts (the modules' pages the query names); $popular (list of [label, url], optional)
--}}
<div class="instant-search flex flex-col flex-1 min-h-0"
     x-data="{
        active: -1,
        recent: (() => { try { return JSON.parse(localStorage.getItem('site-search-recent') || '[]') } catch (e) { return [] } })(),
        options() { return [...$root.querySelectorAll('[role=option]')] },
        move(d) { const o = this.options(); if (! o.length) return; this.active = (this.active + d + o.length) % o.length; o[this.active].scrollIntoView({ block: 'nearest' }) },
        go(e) { const o = this.options(); this.remember(); if (this.active >= 0 && o[this.active]) { e.preventDefault(); o[this.active].click() } },
        remember() { const q = ($wire.q || '').trim(); if (q.length < 2) return; this.recent = [q, ...this.recent.filter(r => r !== q)].slice(0, 5); try { localStorage.setItem('site-search-recent', JSON.stringify(this.recent)) } catch (e) {} },
        forget() { this.recent = []; try { localStorage.removeItem('site-search-recent') } catch (e) {} },
     }" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)">
    <div class="flex-shrink-0 p-6 border-b border-gray-200 bg-white">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-gray-900">{{ __('theme::theme.search') }}</h2>
            <button type="button" class="p-1.5 hover:bg-gray-100 rounded-md transition-colors cursor-pointer" x-on:click="search = false">
                <x-theme::icon name="close" class="w-5 h-5 text-gray-500" />
                <span class="sr-only">{{ __('theme::theme.close') }}</span>
            </button>
        </div>
        <form action="{{ route('search') }}" method="get" role="search" class="relative" x-on:submit="go($event)">
            <label for="instant-search-q" class="sr-only">{{ __('platform::search.label') }}</label>
            <x-theme::icon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" />
            <input id="instant-search-q" type="search" name="q" autocomplete="off" maxlength="100"
                   wire:model.live.debounce.300ms="q" x-on:input="active = -1"
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
                    @foreach ($shortcuts as $link)
                        <a role="option" href="{{ $link['url'] }}" x-on:click="remember()" x-bind:class="active === {{ $loop->index }} && 'ring-2 ring-blue-300'"
                           class="flex items-center justify-between gap-3 rounded-lg bg-secondary px-6 py-4 text-white hover:bg-primary hover:no-underline">
                            <span class="text-lg font-semibold">{{ $link['label'] }}</span>
                            <x-theme::icon name="arrow-up-right" class="size-5 shrink-0" />
                        </a>
                    @endforeach
                    @foreach ($results['items'] as $item)
                        <a role="option" href="{{ $item['url'] }}" x-on:click="remember()" x-on:mouseenter="active = {{ count($shortcuts) + $loop->index }}" x-bind:aria-selected="active === {{ count($shortcuts) + $loop->index }}"
                           x-bind:class="active === {{ count($shortcuts) + $loop->index }} && 'bg-blue-50 ring-1 ring-blue-200'"
                           class="block bg-white border-b border-gray-200 px-6 py-4 transition-all duration-200 group hover:bg-gray-50 hover:no-underline [&_mark]:bg-yellow-200 [&_mark]:text-inherit">
                            <span class="block text-lg font-semibold text-blue-600 group-hover:text-blue-800 mb-1">{{ $item['title'] }}</span>
                            @if ((string) $item['excerpt'] !== '')<span class="block text-sm text-gray-600 mb-2 line-clamp-2">{{ $item['excerpt'] }}</span>@endif
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
            <div class="max-w-2xl mx-auto space-y-8">
                <div x-show="recent.length" x-cloak>
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-semibold uppercase tracking-wider text-gray-500">{{ __('theme::theme.search_recent') }}</p>
                        <button type="button" x-on:click="forget()" class="text-sm text-gray-500 hover:text-gray-800 cursor-pointer">{{ __('theme::theme.search_clear') }}</button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="r in recent" :key="r">
                            <button type="button" x-on:click="$wire.set('q', r)" class="inline-flex items-center gap-1.5 rounded-full border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:border-secondary cursor-pointer">
                                <x-theme::icon name="clock" class="size-4 text-gray-400" /><span x-text="r"></span>
                            </button>
                        </template>
                    </div>
                </div>
                @if (($popular ?? []) !== [])
                    <div>
                        <p class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-500">{{ __('theme::theme.search_popular') }}</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($popular as $link)
                                <a href="{{ $link['url'] }}" class="rounded-full border border-secondary bg-white px-4 py-2 text-sm text-secondary hover:bg-secondary hover:text-white hover:no-underline">{{ $link['label'] }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
