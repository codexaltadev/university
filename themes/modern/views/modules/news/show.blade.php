{{--
    A news post /news/{slug} (UI/UX Part B §12.5): a centred reading column, the big picture, tags, related posts.
    Contract: $post, $related, $layout, $base, $page (NewsPage: listingUrl, image, category, date, views, author, body, tags, related)
--}}
<x-theme::layout :title="$post->title">
    <div class="container pt-12">
        <div class="article article--center">
            <nav class="breadcrumb mb-4" aria-label="{{ __('theme::theme.breadcrumb') }}">
                <ol>
                    <li><a href="{{ url('/') }}">{{ __('theme::theme.home') }}</a><span aria-hidden="true"> / </span></li>
                    <li><a href="{{ $page->listingUrl() }}">{{ __('news::news.plural') }}</a></li>
                </ol>
            </nav>
            @if ($page->category())<span class="badge">{{ $page->category() }}</span>@endif
            <h1 class="mt-3 text-4xl md:text-5xl">{{ $post->title }}</h1>
            @if ($post->subtitle)<p class="lead mt-4">{{ $post->subtitle }}</p>@endif
            <div class="meta-row mt-5">
                <span><x-theme::icon name="calendar" width="16" height="16" />{{ $page->date() }}</span>
                @if ($page->author())<span><x-theme::icon name="user" width="16" height="16" />{{ $page->author() }}</span>@endif
                <span><x-theme::icon name="eye" width="16" height="16" />{{ $page->views() }}</span>
            </div>
        </div>
        @if ($page->image())
            <figure class="article__cover mx-auto max-w-5xl"><img src="{{ $page->image() }}" alt="" fetchpriority="high"></figure>
        @endif
        <article class="article article--center pb-16 @if (! $page->image()) pt-8 @endif">
            @if ($body = $page->body())<div class="prose">{!! $body !!}</div>@endif
            @if ($tags = $page->tags())
                <div class="mt-10 flex flex-wrap gap-2">
                    @foreach ($tags as $tagItem)<a href="{{ $tagItem['url'] }}" class="chip">#{{ $tagItem['name'] }}</a>@endforeach
                </div>
            @endif
        </article>
    </div>
    @if ($cards = $page->related())
        <section class="home-section home-section--alt">
            <div class="container">
                <x-theme::section-head :title="__('news::news.related')" :link="$page->listingUrl()" />
                <div class="grid-cards">
                    @foreach ($cards as $card)
                        @include('theme::partials.news-card', ['card' => $card])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-theme::layout>
