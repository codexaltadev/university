{{--
    News /news and /{entity}/news (UI/UX Part B §12.5): category chips and search, the newest two large, then a card grid
    and the monthly archive. Contract: the module's (posts, categories, category, tag, archive, layout, base),
    $page (NewsPage: listingUrl, url, title, latest, grid, categories, archive)
--}}
<x-theme::layout :title="$page->title()">
    <x-theme::page-hero :title="$page->title()" :crumbs="[['label' => __('news::news.plural'), 'url' => $page->listingUrl()]]">
        <div class="filters">
            <a href="{{ $page->url(['category' => null, 'tag' => null, 'year' => null, 'month' => null]) }}" @class(['chip', 'chip-active' => ! $category && ! $tag && ! request('year')])>{{ __('news::news.all') }}</a>
            @foreach ($page->categories() as $item)
                <a href="{{ $item['url'] }}" @class(['chip', 'chip-active' => $item['current']])>{{ $item['name'] }}</a>
            @endforeach
        </div>
        <form method="get" action="{{ $page->listingUrl() }}" class="filters" role="search">
            <label class="sr-only" for="news-q">{{ __('news::news.search') }}</label>
            <input id="news-q" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('news::news.search_placeholder') }}">
            <button type="submit" class="btn btn--primary">{{ __('news::news.search') }}</button>
        </form>
    </x-theme::page-hero>

    <div class="container page-body">
        @if ($posts->isEmpty())
            <p class="empty-state">{{ __('news::news.none') }}</p>
        @else
            @if ($latest = $page->latest())
                <div class="grid-cards grid-cards--lg mb-6">
                    @foreach ($latest as $card)
                        @include('theme::partials.news-card', ['card' => $card, 'large' => true])
                    @endforeach
                </div>
            @endif
            <div class="grid-cards">
                @foreach ($page->grid() as $card)
                    @include('theme::partials.news-card', ['card' => $card])
                @endforeach
            </div>
            {{ $posts->links() }}
        @endif

        @if ($months = $page->archive())
            <section class="mt-16">
                <h2 class="mb-4 text-xl">{{ __('news::news.archive') }}</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach ($months as $month)
                        <a href="{{ $month['url'] }}" @class(['chip', 'chip-active' => $month['current']])>{{ $month['label'] }} <span class="muted">{{ $month['total'] }}</span></a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-theme::layout>
