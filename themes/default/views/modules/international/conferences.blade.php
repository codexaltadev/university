{{--
    /international/conferences as DIU's /conferences/{category}: hero; the upcoming and the past as DIU's picture cards (4:3
    picture growing on hover, title, venue or category, date). Title, line and picture from Settings › International.
    Contract: the module's (upcoming, past, base), $page (InternationalPage)
--}}
<x-theme::layout :title="$page->text('conferences_title') ?? __('international::international.conferences')">
    @include('theme::partials.diu.hero', [
        'title' => $page->text('conferences_title') ?? __('international::international.conferences'),
        'subtitle' => ($line = $page->text('conferences_text')) ? e($line) : null,
        'image' => $page->image('conferences') ?? theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => $page->breadcrumbs($page->text('conferences_title') ?? __('international::international.conferences'), 'conferences'),
    ])

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-12">
            @foreach (['upcoming' => __('international::international.page.upcoming'), 'past' => __('international::international.page.past')] as $which => $heading)
                @if (($cards = $page->conferences($which)) !== [])
                    <section>
                        <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-6">{{ $heading }}</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach ($cards as $card)
                                <a href="{{ $card['url'] }}" class="conference-card group bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 border border-slate-200 hover:border-slate-300 hover:no-underline">
                                    <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
                                        @if ($card['image'])<img src="{{ $card['image'] }}" alt="{{ $card['title'] }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">@else<div class="w-full h-full bg-gradient-to-br from-secondary to-primary"></div>@endif
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/0 to-black/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    </div>
                                    <div class="p-6">
                                        <h3 class="text-xl font-bold text-slate-900 mb-3 group-hover:text-slate-700 transition-colors line-clamp-2">{{ $card['title'] }}</h3>
                                        @if ($card['venue'] || $card['category'])<p class="flex items-center gap-2 text-sm text-slate-600 mb-2"><x-theme::icon name="map-pin" class="w-4 h-4 shrink-0" /><span class="line-clamp-1">{{ $card['venue'] ?? $card['category'] }}</span></p>@endif
                                        @if ($card['when'])<p class="flex items-center gap-2 text-sm text-slate-500"><x-theme::icon name="calendar" class="w-4 h-4 shrink-0" />{{ $card['when'] }}</p>@endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
            @if ($page->conferences('upcoming') === [] && $page->conferences('past') === [])
                <p class="text-center py-12 text-slate-500">{{ __('international::international.none') }}</p>
            @endif
        </div>
    </div>
</x-theme::layout>
