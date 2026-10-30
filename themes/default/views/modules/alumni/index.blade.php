{{--
    /alumni and /{department|program}/alumni in DIU's AlumniList look (its hero copy "Meet Our Distinguished Alumni"):
    hero (Settings › Alumni title, text, picture); "Share your story" and DIU's "Join the alumni portal"; a search and the
    filters that have choices; four columns of graduates, Pride first; DIU pagination.
    Contract: the module's (alumni, departments, programs, batches, years, layout, base, microsite), $page (AlumniPage)
--}}
<x-theme::layout :title="$page->title()">
    @include('theme::partials.diu.hero', [
        'title' => $page->title(),
        'subtitle' => $page->text() ? e($page->text()) : null,
        'image' => $page->image() ?? theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => $page->breadcrumbs(),
    ])

    <div class="min-h-screen bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-12 lg:py-16">
            @if ($page->submitUrl() || $page->portal())
                <div class="flex flex-wrap items-center gap-3 mb-8">
                    @if ($submit = $page->submitUrl())
                        <x-theme::button :href="$submit" variant="primary" rounded="full" padding="px-5 py-2.5" size="text-base"><x-theme::icon name="graduation-cap" class="w-4 h-4" /> {{ __('alumni::alumni.page.share') }}</x-theme::button>
                    @endif
                    @if ($portal = $page->portal())
                        <x-theme::button :href="$portal['url']" target="_blank" variant="outlineSecondary" rounded="full" padding="px-5 py-2.5" size="text-base">{{ $portal['label'] }} <x-theme::icon name="arrow-up-right" class="w-4 h-4" /></x-theme::button>
                    @endif
                </div>
            @endif

            <form method="get" action="{{ $page->listingUrl() }}" role="search" class="flex flex-col lg:flex-row gap-3 mb-4">
                <div class="relative flex-1 flex items-center">
                    <x-theme::icon name="search" class="absolute left-3.5 w-5 h-5 text-gray-400 pointer-events-none" />
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('alumni::alumni.page.search_placeholder') }}" aria-label="{{ __('alumni::alumni.search') }}"
                           class="w-full pl-11 pr-4 py-3 text-sm sm:text-base rounded-xl border border-gray-200 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary placeholder-gray-400">
                </div>
                @foreach ($page->filters() as $filter)
                    <select name="{{ $filter['name'] }}" onchange="this.form.submit()" aria-label="{{ $filter['label'] }}" class="lg:w-44 py-3 px-4 rounded-xl border border-gray-200 bg-white shadow-sm text-sm sm:text-base text-gray-700">
                        <option value="">{{ $filter['label'] }}: {{ __('alumni::alumni.all') }}</option>
                        @foreach ($filter['options'] as $value => $label)<option value="{{ $value }}" @selected($filter['value'] === (string) $value)>{{ $label }}</option>@endforeach
                    </select>
                @endforeach
                <noscript><button type="submit" class="py-3 px-5 rounded-xl bg-secondary text-white">{{ __('alumni::alumni.search') }}</button></noscript>
            </form>
            <p class="mb-8 text-sm text-gray-500">{{ trans_choice('alumni::alumni.page.count', $page->total(), ['count' => $page->total()]) }}</p>

            @if (($cards = $page->cards()) === [])
                <div class="text-center py-24">
                    <span class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 mb-4"><x-theme::icon name="graduation-cap" class="w-8 h-8 text-gray-400" /></span>
                    <p class="text-lg font-medium text-gray-900 mb-2">{{ __('alumni::alumni.none') }}</p>
                    <p class="text-sm text-gray-600">{{ __('alumni::alumni.page.none_help') }}</p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                    @foreach ($cards as $card)
                        @include('theme::partials.alumni.card', ['card' => $card])
                    @endforeach
                </div>
                {{ $alumni->links('theme::partials.diu.pagination') }}
            @endif
        </div>
    </div>
</x-theme::layout>
