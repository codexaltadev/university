{{--
    DIU News post (news.daffodilvarsity.edu.bd/news/{slug}): the way back, the title, the picture under a
    navy fade with the category, date and hits; the text; then "Explore the Inspiring Stories" and three
    more. Beyond DIU: subtitle, author, video, photos and tags when the post has them. Appearance ›
    Layouts: standard = a narrower column, full width = DIU News'.
    Contract: $post, $related, $layout, $base, $page (NewsPage)
--}}
<x-theme::layout :title="$post->title">
    <article class="bg-white">
        <div @class(['container mx-auto px-4 sm:px-6 lg:px-8 pt-32 lg:pt-36 pb-16', 'news-post--standard max-w-5xl' => $layout !== 'full_width'])>
            <nav class="text-sm text-gray-600" aria-label="{{ __('platform::relations.breadcrumb') }}">
                <a href="{{ url($base ?: '/') }}" class="hover:text-indigo-900">{{ __('theme::theme.home') }}</a> ›
                <a href="{{ $page->listingUrl() }}" class="hover:text-indigo-900">{{ __('news::news.plural') }}</a> ›
                <span aria-current="page">{{ $post->title }}</span>
            </nav>

            <h1 class="mt-8 text-2xl sm:text-3xl font-semibold text-neutral-900 leading-snug">{{ $post->title }}</h1>
            @if (filled($post->subtitle))<p class="mt-3 text-lg text-gray-600">{{ $post->subtitle }}</p>@endif

            <div class="relative mt-5 h-72 sm:h-96 rounded-xl overflow-hidden bg-indigo-950">
                @if ($image = $page->image())<img src="{{ $image }}" alt="{{ $post->title }}" class="absolute inset-0 w-full h-full object-contain">@endif
                <div class="absolute inset-0 bg-gradient-to-b from-white/20 via-indigo-950/40 to-indigo-950"></div>
                <div class="absolute bottom-6 left-6 right-6 flex flex-wrap items-center gap-4 text-sm">
                    @if ($category = $page->category())<span class="bg-amber-400 text-neutral-900 px-2.5 py-1 rounded-full">{{ $category }}</span>@endif
                    <span class="inline-flex items-center gap-1.5 text-white"><x-theme::icon name="clock" class="w-4 h-4 text-amber-400" />{{ $page->date() }}</span>
                    <span class="bg-amber-400 text-neutral-900 px-2.5 py-1 rounded-full">{{ __('news::news.page.hits', ['count' => number_format($page->views())]) }}</span>
                    @if ($author = $page->author())<span class="text-white/90">{{ $author }}</span>@endif
                </div>
            </div>

            @if ($body = $page->body())
                <div class="mt-10 text-lg leading-relaxed text-neutral-900">@include('theme::partials.page.body', ['body' => $body])</div>
            @endif

            @if ($embed = $post->videoEmbed())
                <div class="mt-10 aspect-video rounded-xl overflow-hidden"><iframe src="{{ $embed }}" title="{{ __('news::news.fields.video_url') }}" loading="lazy" allowfullscreen class="w-full h-full border-0"></iframe></div>
            @endif

            @if (($gallery = $post->galleryImages())->isNotEmpty())
                <section class="mt-10" aria-labelledby="news-gallery">
                    <h2 id="news-gallery" class="text-xl font-semibold text-neutral-900 mb-4">{{ __('news::news.fields.gallery') }}</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                        @foreach ($gallery as $item)
                            <a href="{{ $item->url('large') }}" target="_blank" rel="noopener" class="block aspect-[4/3] rounded-lg overflow-hidden bg-gray-100"><img src="{{ $item->url('medium') }}" alt="{{ $item->alt ?? '' }}" loading="lazy" class="w-full h-full object-cover hover:scale-105 transition-transform"></a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if (($tags = $page->tags()) !== [])
                <p class="mt-8 flex flex-wrap gap-2">
                    @foreach ($tags as $tag)<a href="{{ $tag['url'] }}" class="px-3 py-1 rounded-full bg-indigo-50 text-indigo-900 text-sm hover:no-underline">#{{ $tag['name'] }}</a>@endforeach
                </p>
            @endif

            <div class="mt-20">
                @include('theme::partials.news.explore', ['cards' => $page->related(), 'more' => $page->listingUrl()])
            </div>
        </div>
    </article>
</x-theme::layout>
