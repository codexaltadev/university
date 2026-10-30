{{--
    /achievements/{slug} in DIU's event-page look: hero with the title; picture, title and story beside the details
    (level, position, date, competition, issuer, team); the achievers, photos and the news post.
    Contract: $achievement, $achievers, $gallery, $post, $base, $page (AchievementsPage)
--}}
<x-theme::layout :title="$achievement->title">
    @include('theme::partials.diu.hero', [
        'title' => $achievement->title,
        'subtitle' => null,
        'image' => theme_asset('images/event-hero.webp'),
        'breadcrumbs' => [['label' => __('theme::theme.home'), 'url' => url($base ?: '/')], ['label' => __('achievements::achievements.plural'), 'url' => $page->listingUrl()], ['label' => $achievement->title, 'url' => request()->url()]],
    ])

    <div class="min-h-screen bg-gray-50">
        <div class="container mx-auto px-4 py-12">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-8 min-w-0">
                    <article class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        @if ($picture = $achievement->firstMedia('image'))
                            <div class="aspect-video w-full overflow-hidden"><img src="{{ $picture->url('large') ?? $picture->url() }}" alt="{{ $picture->alt_text ?: $achievement->title }}" class="w-full h-full object-cover"></div>
                        @endif
                        <div class="p-8">
                            <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-6 leading-tight">{{ $achievement->title }}</h1>
                            @if ($achievement->summary)<p class="text-lg text-gray-600 mb-6">{{ $achievement->summary }}</p>@endif
                            @if ($body = $page->body())@include('theme::partials.page.body', ['body' => $body])@endif
                        </div>
                    </article>

                    @if ($achievers->isNotEmpty())
                        <section class="bg-white rounded-2xl shadow-lg p-8" aria-labelledby="ach-people">
                            <h2 id="ach-people" class="text-2xl font-bold text-gray-900 mb-6">{{ __('achievements::achievements.sections.achievers') }}</h2>
                            <div class="grid sm:grid-cols-2 gap-4">
                                @foreach ($achievers as $person)
                                    <div class="flex items-center gap-4 border border-gray-100 rounded-xl p-4">
                                        <div class="w-14 h-14 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center shrink-0">@if ($person->photoUrl())<img loading="lazy" decoding="async" src="{{ $person->photoUrl() }}" alt="" class="w-full h-full object-cover">@else<x-theme::icon name="user" class="w-6 h-6 text-gray-400" />@endif</div>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-900">@if ($person->profileUrl())<a href="{{ $person->profileUrl() }}" class="hover:text-primary">{{ $person->name() }}</a>@else{{ $person->name() }}@endif</p>
                                            @if ($person->note() || $person->designation())<p class="text-sm text-gray-500">{{ $person->note() ?: $person->designation() }}</p>@endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if ($gallery->isNotEmpty())
                        <section class="bg-white rounded-2xl shadow-lg p-8" aria-labelledby="ach-gallery">
                            <h2 id="ach-gallery" class="text-2xl font-bold text-gray-900 mb-6">{{ __('achievements::achievements.fields.gallery') }}</h2>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                @foreach ($gallery as $item)
                                    <a href="{{ $item->url('large') ?? $item->url() }}" target="_blank" rel="noopener" class="block aspect-[4/3] rounded-lg overflow-hidden bg-gray-100"><img decoding="async" src="{{ $item->url('medium') ?? $item->url() }}" alt="{{ $item->alt_text ?? '' }}" loading="lazy" class="w-full h-full object-cover hover:scale-105 transition-transform"></a>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>

                <aside class="lg:col-span-1">
                    <div class="bg-white rounded-2xl p-6 sticky top-28 space-y-4">
                        <h2 class="text-xl font-bold text-gray-900 mb-2">{{ __('achievements::achievements.page.details') }}</h2>
                        @foreach ($page->details() as $detail)
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center mt-1 shrink-0"><x-theme::icon :name="$detail['icon']" class="w-4 h-4 text-gray-600" /></div>
                                <div class="min-w-0"><p class="text-sm font-medium text-gray-500">{{ $detail['label'] }}</p><p class="text-gray-900 font-medium">{{ $detail['value'] }}</p></div>
                            </div>
                        @endforeach
                        @if ($post)
                            <a href="{{ url($post->url()) }}" class="mt-4 w-full bg-gray-100 hover:bg-gray-200 text-gray-900 font-medium py-3 px-4 rounded-lg flex items-center justify-center gap-2 hover:no-underline">{{ __('achievements::achievements.read_news') }}</a>
                        @endif
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-theme::layout>
