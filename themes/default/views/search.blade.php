{{--
    Search page (FR-SRCH-02/06) in the look of DIU's search window, as a page: hero with a big search field; "Found N results
    for …" and the results by module as tabs with their counts; each result — its title with the words marked, the module as
    a blue chip, the text around the words and its address; DIU's page numbers; with nothing found or nothing asked, the hints
    and the popular searches (Settings › Search, from the SEO module).
    Contract (kernel): $results (query, total, groups: slug => [label, count], items: paginator of [title, excerpt, url, module,
    label]), $module, $scope, $scopeLabel, $shortcuts (the modules' pages the query names: list of [label, url]); $popular (list of [label, url], optional)
--}}
<x-theme::layout :title="__('platform::search.title')">
    @include('theme::partials.diu.hero', [
        'title' => $results['query'] !== '' ? $results['query'] : __('platform::search.title'),
        'subtitle' => null,
        'image' => theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => [['label' => __('theme::theme.home'), 'url' => url('/')], ['label' => __('platform::search.title'), 'url' => route('search')]],
    ])

    <div class="bg-gray-50 min-h-[50vh]">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-10">
            <form action="{{ route('search') }}" method="get" role="search" class="relative -mt-20 mb-8">
                <label for="search-q" class="sr-only">{{ __('platform::search.label') }}</label>
                <x-theme::icon name="search" class="absolute left-5 top-1/2 -translate-y-1/2 size-6 text-gray-400 pointer-events-none" />
                <input id="search-q" type="search" name="q" value="{{ $results['query'] }}" maxlength="100" autofocus
                       placeholder="{{ __('theme::theme.search_placeholder') }}"
                       class="w-full rounded-2xl border border-gray-200 bg-white pl-14 pr-36 py-5 text-lg shadow-xl outline-none focus:ring-2 focus:ring-primary/30">
                @if ($scope)<input type="hidden" name="in" value="{{ $scope }}">@endif
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-xl bg-primary px-6 py-3 font-semibold text-white hover:bg-secondary cursor-pointer">{{ __('platform::search.button') }}</button>
            </form>

            @if ($scope)
                <p class="mb-4 text-sm text-gray-600">{{ __('platform::search.only_in', ['name' => $scopeLabel]) }}
                    · <a href="{{ route('search', array_filter(['q' => $results['query'], 'module' => $module])) }}" class="font-semibold text-primary">{{ __('platform::search.everywhere') }}</a></p>
            @endif

            @if (($shortcuts ?? []) !== [])
                <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($shortcuts as $link)
                        <a href="{{ $link['url'] }}" class="group flex items-center justify-between gap-3 rounded-xl bg-secondary px-5 py-4 text-white shadow-md hover:bg-primary hover:no-underline">
                            <span class="font-semibold">{{ $link['label'] }}</span>
                            <x-theme::icon name="arrow-up-right" class="size-5 shrink-0 transition-transform group-hover:rotate-45" />
                        </a>
                    @endforeach
                </div>
            @endif

            @if ($results['query'] !== '' && $results['total'] > 0)
                <p class="mb-4 text-sm text-gray-600" aria-live="polite">{{ __('theme::theme.search_found') }} <span class="font-semibold text-blue-600">{{ $results['total'] }}</span>
                    {{ trans_choice('theme::theme.search_results', $results['total']) }} "<span class="font-medium">{{ $results['query'] }}</span>"</p>

                <nav class="mb-6 flex gap-2 overflow-x-auto pb-2" aria-label="{{ __('platform::search.filter') }}">
                    <a href="{{ route('search', array_filter(['q' => $results['query'], 'in' => $scope])) }}" @if (! $module) aria-current="page" @endif
                       @class(['shrink-0 rounded-full border px-4 py-2 text-sm font-medium hover:no-underline', 'bg-secondary text-white border-secondary' => ! $module, 'bg-white text-gray-700 border-gray-200 hover:border-secondary' => $module])>{{ __('platform::search.all') }} <span class="opacity-70">({{ $results['total'] }})</span></a>
                    @foreach ($results['groups'] as $slug => $group)
                        <a href="{{ route('search', array_filter(['q' => $results['query'], 'module' => $slug, 'in' => $scope])) }}" @if ($module === $slug) aria-current="page" @endif
                           @class(['shrink-0 rounded-full border px-4 py-2 text-sm font-medium hover:no-underline', 'bg-secondary text-white border-secondary' => $module === $slug, 'bg-white text-gray-700 border-gray-200 hover:border-secondary' => $module !== $slug])>{{ $group['label'] }} <span class="opacity-70">({{ $group['count'] }})</span></a>
                    @endforeach
                </nav>

                <ol class="space-y-3" role="list">
                    @foreach ($results['items'] as $item)
                        <li>
                            <a href="{{ $item['url'] }}" class="group block rounded-xl border border-gray-200 bg-white px-6 py-5 transition-all hover:border-primary/40 hover:shadow-md hover:no-underline [&_mark]:bg-yellow-200 [&_mark]:text-inherit [&_mark]:rounded-sm">
                                <span class="inline-block bg-blue-100 text-blue-800 text-xs px-3 py-1 rounded-full font-medium mb-2">{{ $item['label'] }}</span>
                                <span class="block text-lg font-semibold text-blue-600 group-hover:text-blue-800">{{ $item['title'] }}</span>
                                @if ((string) $item['excerpt'] !== '')<span class="mt-1 block text-sm leading-relaxed text-gray-600">{{ $item['excerpt'] }}</span>@endif
                                <span class="mt-2 block truncate text-xs text-green-700">{{ $item['url'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ol>

                {{ $results['items']->links('theme::partials.diu.pagination') }}
            @else
                <div class="text-center py-12">
                    <x-theme::icon name="search" width="64" height="64" class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                    @if ($results['query'] !== '')
                        <p class="text-gray-600 text-lg font-medium mb-2">{{ __('theme::theme.search_none') }}</p>
                        <p class="text-gray-500">{{ __('theme::theme.search_for', ['q' => $results['query']]) }} — {{ __('theme::theme.search_try') }}</p>
                    @else
                        <p class="text-gray-600 text-lg font-medium mb-2">{{ __('theme::theme.search_start') }}</p>
                        <p class="text-gray-500">{{ __('theme::theme.search_hint') }}</p>
                    @endif
                    @if (($popular ?? []) !== [])
                        <p class="mt-8 mb-3 text-sm font-semibold uppercase tracking-wider text-gray-500">{{ __('theme::theme.search_popular') }}</p>
                        <div class="flex flex-wrap justify-center gap-2">
                            @foreach ($popular as $link)
                                <a href="{{ $link['url'] }}" class="rounded-full border border-secondary bg-white px-4 py-2 text-secondary hover:bg-secondary hover:text-white hover:no-underline">{{ $link['label'] }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-theme::layout>
