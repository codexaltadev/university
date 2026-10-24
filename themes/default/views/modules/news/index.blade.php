{{--
    DIU News (news.daffodilvarsity.edu.bd: home, category-news): on the first page "Latest" — big heading,
    Settings › News' text, the newest post beside and the next one under it; then the posts as cards
    (picture, navy fade, category pill, title) beside "All News" with the way back, a search and
    "Filter by Category"; page numbers.
    Contract: the module's (posts, categories, category, tag, archive, layout, base), $page (NewsPage)
--}}
<x-theme::layout :title="$page->title()">
    <div class="bg-white">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 pt-32 lg:pt-36 pb-16">
            @if ($latest = $page->latest())
                {{-- The newest comes first in the page, on the right on large screens --}}
                <section class="grid lg:grid-cols-2 gap-6 mb-16" aria-labelledby="news-latest">
                    <div class="lg:col-start-1 lg:row-start-1">
                        <h1 id="news-latest" class="text-6xl sm:text-7xl lg:text-8xl font-black text-neutral-900 tracking-tight">{{ settings('news.latest_title') ?: __('news::news.plural') }}</h1>
                        @if (filled(settings('news.latest_text')))<p class="mt-6 text-gray-600 leading-relaxed max-w-xl">{{ settings('news.latest_text') }}</p>@endif
                    </div>
                    @if ($first = $latest[0] ?? null)
                    <a href="{{ $first['url'] }}" class="group flex flex-col rounded-xl overflow-hidden hover:no-underline lg:col-start-2 lg:row-start-1 lg:row-span-2">
                        <span class="block h-64 sm:h-80 bg-indigo-950 rounded-t-xl overflow-hidden">@if ($first['image'])<img src="{{ $first['image'] }}" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">@endif</span>
                        <span class="block bg-indigo-50 p-7 rounded-b-xl flex-1">
                            <span class="block text-xl font-semibold text-indigo-950 leading-snug">{{ $first['title'] }}</span>
                            @if ($first['text'])<span class="block mt-4 font-semibold text-gray-600 line-clamp-3">{{ $first['text'] }}</span>@endif
                            <span class="flex flex-wrap items-center gap-3 mt-5">
                                @if ($first['category'])<span class="bg-amber-400 text-neutral-900 px-4 py-1.5 rounded-full">{{ $first['category'] }}</span>@endif
                                <span class="inline-flex items-center gap-1.5 text-gray-700"><x-theme::icon name="clock" class="w-4 h-4 text-amber-500" />{{ $first['date'] }}</span>
                            </span>
                        </span>
                    </a>
                    @endif
                    @if ($second = $latest[1] ?? null)
                        <a href="{{ $second['url'] }}" class="group grid grid-cols-1 sm:grid-cols-2 rounded-xl overflow-hidden bg-indigo-50 hover:no-underline lg:col-start-1 lg:row-start-2 self-end">
                            <span class="block h-56 sm:h-full bg-indigo-950">@if ($second['image'])<img src="{{ $second['image'] }}" alt="" class="w-full h-full object-cover">@endif</span>
                            <span class="block p-5">
                                <span class="block font-semibold text-indigo-950 leading-snug">{{ $second['title'] }}</span>
                                @if ($second['text'])<span class="block mt-3 text-sm text-gray-600 text-center line-clamp-3">{{ $second['text'] }}</span>@endif
                                <span class="flex flex-wrap items-center gap-3 mt-4 text-sm">
                                    @if ($second['category'])<span class="bg-amber-400 text-neutral-900 px-3 py-1 rounded-full">{{ $second['category'] }}</span>@endif
                                    <span class="inline-flex items-center gap-1 text-gray-700"><x-theme::icon name="clock" class="w-4 h-4 text-amber-500" />{{ $second['date'] }}</span>
                                </span>
                            </span>
                        </a>
                    @endif
                </section>
            @endif

            <div class="flex flex-col-reverse lg:flex-row gap-10">
                <div class="flex-1 min-w-0">
                    @if (($grid = $page->grid()) === [] && $latest === [])
                        <p class="text-gray-500 py-12 text-center">{{ __('news::news.page.none') }}</p>
                    @else
                        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-6">
                            @foreach ($grid as $card)
                                @include('theme::partials.news.overlay', ['card' => $card])
                            @endforeach
                        </div>
                        {{ $posts->onEachSide(1)->links('theme::partials.diu.pagination') }}
                    @endif
                </div>

                <aside class="lg:w-[340px] shrink-0 space-y-10">
                    <div>
                        @if ($latest === [])<h1 class="text-2xl font-bold text-neutral-900">{{ $page->title() }}</h1>@else<h2 class="text-2xl font-bold text-neutral-900">{{ $page->title() }}</h2>@endif
                        <nav class="flex items-center gap-2 mt-3 text-sm text-gray-500" aria-label="{{ __('platform::relations.breadcrumb') }}">
                            <a href="{{ url($base ?: '/') }}" class="hover:text-indigo-900">{{ __('theme::theme.home') }}</a>
                            <x-theme::icon name="chevron-right" class="w-3 h-3" />
                            <a href="{{ $page->listingUrl() }}" class="hover:text-indigo-900">{{ __('news::news.plural') }}</a>
                        </nav>
                    </div>
                    <form method="get" action="{{ $page->listingUrl() }}" role="search">
                        <label for="news-search" class="block font-semibold text-neutral-900 mb-3">{{ __('news::news.page.search') }}</label>
                        @if ($category)<input type="hidden" name="category" value="{{ $category->slug() }}">@endif
                        <input id="news-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('news::news.page.type_here') }}"
                               class="w-full border-b border-gray-200 px-3 py-2 text-sm outline-none focus:border-indigo-900">
                    </form>
                    @if (($categories = $page->categories()) !== [])
                        <div>
                            <h2 class="font-semibold text-neutral-900 mb-2">{{ __('news::news.page.filter') }}</h2>
                            <ul>
                                <li><a href="{{ $page->url(['category' => null, 'tag' => null]) }}" @class(['block py-4 border-b border-gray-200 hover:text-indigo-900 hover:no-underline', 'text-indigo-900 font-semibold' => ! $category && ! $tag, 'text-gray-700' => $category || $tag])>{{ settings('news.all_title') ?: __('news::news.page.all') }}</a></li>
                                @foreach ($categories as $item)
                                    <li><a href="{{ $item['url'] }}" @class(['block py-4 border-b border-gray-200 hover:text-indigo-900 hover:no-underline', 'text-indigo-900 font-semibold' => $item['current'], 'text-gray-700' => ! $item['current']])>{{ $item['name'] }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if (($months = $page->archive()) !== [])
                        <div>
                            <h2 class="font-semibold text-neutral-900 mb-2">{{ __('news::news.page.archive') }}</h2>
                            <ul class="text-sm">
                                @foreach ($months as $month)
                                    <li><a href="{{ $month['url'] }}" @class(['flex justify-between py-2 border-b border-gray-100 hover:text-indigo-900 hover:no-underline', 'text-indigo-900 font-semibold' => $month['current'], 'text-gray-700' => ! $month['current']])><span>{{ $month['label'] }}</span><span class="text-gray-400">{{ $month['total'] }}</span></a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </div>
</x-theme::layout>
