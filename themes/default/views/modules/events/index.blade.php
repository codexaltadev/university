{{--
    DIU /events (DIUFronend app/(public)/events, EventFilter, UI/EventCard): hero "Explore Our Events"; the
    search, the Upcoming / Archive switch (and Calendar) and Select Faculty; "Upcoming Events" with
    Settings › Events' line; a card per event with its date. Categories, when there are any, as chips.
    Contract: the module's (events, categories, category, q, faculty, layout, tab, month, weeks, base), $page (EventsPage)
--}}
<x-theme::layout :title="settings('events.listing_title') ?: __('events::events.plural')">
    @include('theme::partials.diu.hero', [
        'title' => settings('events.listing_title') ?: __('events::events.plural'),
        'subtitle' => e(settings('events.listing_text')),
        'image' => media_url(settings('events.listing_image'), 'large') ?? theme_asset('images/faculty-banner.webp'),
        'breadcrumbs' => [['label' => __('theme::theme.home'), 'url' => url($base ?: '/')], ['label' => __('events::events.plural'), 'url' => $page->listingUrl()]],
    ])

    <div class="min-h-screen bg-white">
        <div class="container mx-auto px-6 py-12">
            {{-- Filters --}}
            <div class="bg-white border-b border-gray-100 pb-6 mb-8 sm:pb-8">
                <div class="flex flex-col lg:flex-row gap-4 items-stretch">
                    <form method="get" action="{{ $page->listingUrl() }}" class="relative flex-1 min-w-0 w-full lg:w-auto" role="search">
                        @foreach (['tab' => $tab === 'past' ? 'past' : null, 'view' => request('view'), 'category' => $category?->slug(), 'faculty' => $faculty ? mb_strtolower($faculty->short_name) : null] as $name => $value)
                            @if ($value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif
                        @endforeach
                        <x-theme::icon name="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                        <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('events::events.page.search') }}" aria-label="{{ __('events::events.page.search') }}"
                               class="w-full h-[54px] pl-12 pr-5 text-base rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all outline-none text-gray-700">
                    </form>

                    <nav class="flex bg-gray-100 rounded-xl p-1.5 w-full lg:w-auto h-[54px] items-center" aria-label="{{ __('events::events.tabs.label') }}">
                        @foreach ([
                            ['key' => 'upcoming', 'url' => $page->url(['tab' => null, 'view' => null, 'month' => null]), 'icon' => 'calendar', 'label' => __('events::events.page.upcoming'), 'short' => __('events::events.page.upcoming_short'), 'on' => ! $page->isCalendar() && $tab === 'upcoming'],
                            ['key' => 'past', 'url' => $page->url(['tab' => 'past', 'view' => null, 'month' => null]), 'icon' => 'archive', 'label' => __('events::events.page.archive'), 'short' => __('events::events.page.archive_short'), 'on' => ! $page->isCalendar() && $tab === 'past'],
                            ['key' => 'calendar', 'url' => $page->url(['tab' => null, 'view' => 'calendar']), 'icon' => 'grid', 'label' => __('events::events.page.calendar'), 'short' => __('events::events.page.calendar'), 'on' => $page->isCalendar()],
                        ] as $switch)
                            <a href="{{ $switch['url'] }}" @if ($switch['on']) aria-current="page" @endif
                               @class(['relative flex items-center justify-center gap-1.5 sm:gap-2 px-2.5 sm:px-5 font-medium text-sm flex-1 lg:flex-initial h-full hover:no-underline transition-colors', 'bg-primary text-white rounded-full' => $switch['on'], 'text-gray-600 hover:text-gray-800 rounded-lg' => ! $switch['on']])>
                                <x-theme::icon :name="$switch['icon']" class="w-4 h-4" />
                                <span class="hidden sm:inline">{{ $switch['label'] }}</span>
                                <span class="sm:hidden">{{ $switch['short'] }}</span>
                            </a>
                        @endforeach
                    </nav>

                    @if (($faculties = $page->faculties()) !== [])
                        <div class="relative w-full lg:min-w-[380px] lg:w-auto" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                            <button type="button" @click="open = ! open" :aria-expanded="open"
                                    class="flex items-center gap-4 bg-gray-50 hover:bg-gray-100 rounded-xl px-6 text-gray-700 font-semibold transition-all w-full justify-between text-base hover:shadow-md h-[54px] cursor-pointer">
                                <span class="flex items-center gap-4 flex-1 min-w-0">
                                    <x-theme::icon name="filter" class="w-5 h-5 shrink-0" />
                                    <span class="truncate text-left">{{ $faculty?->name ?? __('events::events.page.select_faculty') }}</span>
                                </span>
                                <x-theme::icon name="chevron-down" class="w-5 h-5 shrink-0 transition-transform" ::class="open && 'rotate-180'" />
                            </button>
                            <div x-show="open" x-cloak x-transition class="absolute top-full left-0 right-0 mt-3 bg-white rounded-2xl shadow-2xl border-2 border-gray-200 overflow-hidden z-50 w-full lg:min-w-[400px]">
                                <div class="max-h-[70vh] sm:max-h-96 overflow-y-auto">
                                    <a href="{{ $page->url(['faculty' => null]) }}" class="block w-full px-5 sm:px-7 py-4 sm:py-5 text-left hover:bg-blue-50 transition-colors text-gray-700 text-base sm:text-lg font-semibold bg-gray-50 hover:no-underline">{{ __('events::events.page.all_faculties') }}</a>
                                    @foreach ($faculties as $item)
                                        <a href="{{ $page->url(['faculty' => $item['slug']]) }}" @if ($item['current']) aria-current="true" @endif
                                           @class(['block w-full px-5 sm:px-7 py-4 sm:py-5 text-left hover:bg-blue-50 transition-colors border-t border-gray-100 text-sm sm:text-base leading-relaxed hover:no-underline', 'text-primary font-semibold' => $item['current'], 'text-gray-700' => ! $item['current']])>{{ $item['name'] }}</a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                @if ($categories->isNotEmpty())
                    <nav class="flex flex-wrap gap-2 mt-4" aria-label="{{ __('events::events.fields.category') }}">
                        <a href="{{ $page->url(['category' => null]) }}" @class(['px-4 py-1.5 rounded-full text-sm font-medium border hover:no-underline', 'bg-primary text-white border-primary' => ! $category, 'border-gray-200 text-gray-600 hover:border-gray-900' => $category])>{{ __('events::events.all') }}</a>
                        @foreach ($categories as $item)
                            <a href="{{ $page->url(['category' => $item->slug()]) }}" @class(['px-4 py-1.5 rounded-full text-sm font-medium border hover:no-underline', 'bg-primary text-white border-primary' => $category?->is($item), 'border-gray-200 text-gray-600 hover:border-gray-900' => ! $category?->is($item)])>{{ $item->name }}</a>
                        @endforeach
                    </nav>
                @endif

                @if ($q !== '' || $faculty)
                    <div class="mt-4 pt-4 border-t border-gray-100 flex items-center gap-2 flex-wrap">
                        <span class="text-sm text-gray-500">{{ __('events::events.page.active') }}</span>
                        @if ($q !== '')
                            <a href="{{ $page->url(['q' => null]) }}" class="flex items-center gap-1 bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm hover:no-underline">{{ __('events::events.page.search_for', ['q' => $q]) }} <x-theme::icon name="close" class="w-3 h-3" /></a>
                        @endif
                        @if ($faculty)
                            <a href="{{ $page->url(['faculty' => null]) }}" class="flex items-center gap-1 bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm hover:no-underline">{{ __('events::events.page.faculty_is', ['name' => $faculty->name]) }} <x-theme::icon name="close" class="w-3 h-3" /></a>
                        @endif
                    </div>
                @endif
            </div>

            <div class="mb-10">
                <h2 class="lg:leading-16 text-[24px] md:text-3xl lg:text-5xl font-semibold text-neutral-800 mb-4">
                    {{ $page->isCalendar() ? $month->translatedFormat('F Y') : ($tab === 'past' ? __('events::events.page.archive') : __('events::events.page.upcoming')) }}
                </h2>
                @if (filled(settings('events.intro')))<p class="text-gray-600 text-base max-w-2xl">{{ settings('events.intro') }}</p>@endif
            </div>

            @if ($page->isCalendar())
                <div class="flex items-center justify-between mb-4">
                    <a href="{{ $page->url(['view' => 'calendar', 'month' => $month->copy()->subMonth()->format('Y-m')]) }}" class="inline-flex items-center gap-1 text-gray-700 hover:text-primary hover:no-underline"><x-theme::icon name="chevron-left" class="w-4 h-4" />{{ $month->copy()->subMonth()->translatedFormat('F') }}</a>
                    <a href="{{ $page->url(['view' => 'calendar', 'month' => $month->copy()->addMonth()->format('Y-m')]) }}" class="inline-flex items-center gap-1 text-gray-700 hover:text-primary hover:no-underline">{{ $month->copy()->addMonth()->translatedFormat('F') }}<x-theme::icon name="chevron-right" class="w-4 h-4" /></a>
                </div>
                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="events-calendar w-full table-fixed min-w-[640px] text-sm">
                        <thead class="bg-gray-50"><tr>@foreach ($weeks[0] ?? [] as $cell)<th scope="col" class="py-3 font-semibold text-gray-600">{{ $cell['day']->translatedFormat('D') }}</th>@endforeach</tr></thead>
                        <tbody>
                            @foreach ($weeks as $week)
                                <tr>
                                    @foreach ($week as $cell)
                                        <td @class(['align-top h-28 p-2 border-t border-gray-100', 'bg-gray-50/60 text-gray-300' => ! $cell['inMonth']])>
                                            <div @class(['text-xs mb-1', 'font-bold text-primary' => $cell['day']->isToday()])>{{ $cell['day']->day }}</div>
                                            @if ($cell['inMonth'])
                                                @foreach ($cell['events'] as $item)
                                                    <a href="{{ $page->eventUrl($item) }}" title="{{ $item->title }}" class="block truncate text-xs mt-1 px-1.5 py-0.5 rounded bg-primary text-white hover:no-underline">{{ $item->title }}</a>
                                                @endforeach
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif (($cards = $page->cards()) === [])
                <p class="text-gray-500 text-center py-12">{{ $q !== '' || $faculty ? __('events::events.page.none') : ($tab === 'past' ? __('events::events.no_past') : __('events::events.no_upcoming')) }}</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    @foreach ($cards as $card)
                        <a href="{{ $card['url'] }}" class="bg-lime-50/40 relative p-3 md:p-6 rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 hover:no-underline">
                            <div class="relative pb-4">
                                @if ($card['image'])
                                    <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}" loading="lazy" class="rounded-xl w-full h-[220px] object-cover">
                                @else
                                    <div class="rounded-xl w-full h-[220px] bg-gray-100 flex items-center justify-center"><x-theme::icon name="clock" class="w-12 h-12 text-gray-300" /></div>
                                @endif
                                <div class="absolute top-10 bg-white py-2 px-2 z-10"><span class="text-base text-neutral-800 font-semibold">{{ $card['date'] }}</span></div>
                            </div>
                            <h3 class="text-[24px] font-semibold text-neutral-800 line-clamp-3 leading-tight">{{ $card['title'] }}</h3>
                            <p class="text-gray-500 py-4">{{ $card['date'] }}</p>
                            @if ($card['text'])<p class="text-base text-gray-500 mb-6 leading-relaxed line-clamp-2">{{ $card['text'] }}</p>@endif
                        </a>
                    @endforeach
                </div>
                {{ $events->links('theme::partials.diu.pagination') }}
            @endif
        </div>
    </div>
</x-theme::layout>
