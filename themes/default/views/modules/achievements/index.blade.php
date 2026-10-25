{{--
    /achievements in DIU's card look (its events cards, the Registrar office's Honour Board): hero; the levels as a
    switch, year and category; a card per achievement — picture, level and position on it, title, date, a line.
    Contract: the module's (achievements, categories, category, level, year, years, layout, base), $page (AchievementsPage)
--}}
<x-theme::layout :title="__('achievements::achievements.plural')">
    @include('theme::partials.diu.hero', [
        'title' => __('achievements::achievements.plural'),
        'subtitle' => null,
        'image' => theme_asset('images/faculty-banner.jpg'),
        'breadcrumbs' => [['label' => __('theme::theme.home'), 'url' => url($base ?: '/')], ['label' => __('achievements::achievements.plural'), 'url' => $page->listingUrl()]],
    ])

    <div class="min-h-screen bg-white">
        <div class="container mx-auto px-6 py-12">
            <div class="flex flex-col lg:flex-row gap-4 items-stretch pb-6 mb-8 border-b border-gray-100">
                <nav class="flex flex-wrap bg-gray-100 rounded-xl p-1.5 gap-1" aria-label="{{ __('achievements::achievements.fields.level') }}">
                    @foreach ($page->levels() as $item)
                        <a href="{{ $item['url'] }}" @class(['px-5 py-2.5 text-sm font-medium hover:no-underline', 'bg-primary text-white rounded-full' => $item['current'], 'text-gray-600 hover:text-gray-800 rounded-lg' => ! $item['current']])>{{ $item['label'] }}</a>
                    @endforeach
                </nav>
                <form method="get" class="flex flex-wrap gap-3 lg:ml-auto">
                    @if ($level)<input type="hidden" name="level" value="{{ $level }}">@endif
                    @if ($years !== [])
                        <select name="year" onchange="this.form.submit()" aria-label="{{ __('achievements::achievements.year') }}" class="h-[54px] rounded-xl bg-gray-50 px-4 text-gray-700 font-semibold">
                            <option value="">{{ __('achievements::achievements.year') }}</option>
                            @foreach ($years as $y)<option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>@endforeach
                        </select>
                    @endif
                    @if ($categories->isNotEmpty())
                        <select name="category" onchange="this.form.submit()" aria-label="{{ __('achievements::achievements.fields.category') }}" class="h-[54px] rounded-xl bg-gray-50 px-4 text-gray-700 font-semibold">
                            <option value="">{{ __('achievements::achievements.fields.category') }}</option>
                            @foreach ($categories as $c)<option value="{{ $c->slug() }}" @selected($category?->is($c))>{{ $c->name }}</option>@endforeach
                        </select>
                    @endif
                    <noscript><button type="submit" class="h-[54px] px-5 rounded-xl bg-gray-900 text-white">{{ __('achievements::achievements.filter') }}</button></noscript>
                </form>
            </div>

            @if (($cards = $page->cards()) === [])
                <p class="text-center text-gray-500 py-12">{{ __('achievements::achievements.none') }}</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    @foreach ($cards as $card)
                        <a href="{{ $card['url'] }}" class="achievement-card--{{ $layout }} bg-lime-50/40 relative p-3 md:p-6 rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 hover:no-underline">
                            <div class="relative pb-4">
                                @if ($card['image'])
                                    <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}" loading="lazy" class="rounded-xl w-full h-[220px] object-cover">
                                @else
                                    <div class="rounded-xl w-full h-[220px] bg-gradient-to-br from-secondary to-primary flex items-center justify-center"><x-theme::icon name="graduation-cap" class="w-14 h-14 text-white/80" /></div>
                                @endif
                                <div class="absolute top-10 bg-white py-2 px-2 z-10"><span class="text-base text-neutral-800 font-semibold">{{ $card['level'] }}@if ($card['position']) · {{ $card['position'] }}@endif</span></div>
                            </div>
                            <h2 class="text-[24px] font-semibold text-neutral-800 line-clamp-3 leading-tight">{{ $card['title'] }}</h2>
                            <p class="text-gray-500 py-4">{{ $card['date'] }}</p>
                            @if ($card['text'])<p class="text-base text-gray-500 mb-6 leading-relaxed line-clamp-2">{{ $card['text'] }}</p>@endif
                        </a>
                    @endforeach
                </div>
                {{ $achievements->links('theme::partials.diu.pagination') }}
            @endif
        </div>
    </div>
</x-theme::layout>
