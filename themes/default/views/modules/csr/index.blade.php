{{--
    /csr (and /csr/category/{slug}) as DIU's /csr-activities (DIUFronend components/CSR): hero; the featured activity — title,
    big picture — with the impact numbers and "Our Approach to Social Responsibility" (the next highlighted ones as large
    picture cards), the CSR Report; then on dark navy "Where We Create Impact" (search, category select, the focus areas as
    cards with picture and line) and "Stories of Change" (cards: picture with the date, title, category; DIU pagination).
    Texts, numbers and report from Settings › Social Responsibility.
    Contract: the module's (category, children, activities, categories, stories, picked, q, featured, layout, base), $page (CsrPage)
--}}
<x-theme::layout :title="$page->title()">
    @include('theme::partials.diu.hero', [
        'title' => $page->title(),
        'subtitle' => $page->subtitle() ? e($page->subtitle()) : null,
        'image' => $page->image() ?? theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => $page->breadcrumbs(),
    ])

    @if ($page->isHome() && ($featured = $page->featured()))
        <section class="bg-white">
            <div class="container mx-auto px-4 py-16">
                <a href="{{ $featured['url'] }}" class="hover:no-underline"><h2 class="text-xl md:text-4xl font-bold text-neutral-800 leading-[1.35] mb-6 hover:text-primary">{{ $featured['title'] }}</h2></a>
                @if ($featured['image'])
                    <div class="mb-8 rounded overflow-hidden"><img src="{{ $featured['image'] }}" alt="{{ $featured['title'] }}" class="w-full max-h-[555px] object-cover block"></div>
                @endif

                @if (($stats = $page->stats()) !== [])
                    <div @class(['grid grid-cols-1 border border-gray-200 rounded-md overflow-hidden mb-14 bg-white',
                                 'sm:grid-cols-2' => count($stats) === 2, 'sm:grid-cols-3' => count($stats) === 3 || count($stats) > 4, 'sm:grid-cols-4' => count($stats) === 4])>
                        @foreach ($stats as $stat)
                            <div @class(['flex flex-col items-center justify-center py-7 px-3', 'sm:border-r border-b sm:border-b-0 border-gray-200' => ! $loop->last])>
                                <span class="text-[34px] font-bold text-secondary leading-none mb-1.5">{{ $stat['value'] }}</span>
                                <span class="text-[13px] text-gray-500">{{ $stat['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if (($approach = $page->approach()) !== [])
                    <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4 mb-10">
                        <h2 class="text-2xl md:text-4xl font-bold text-gray-900">{{ $page->text('approach_title') }}</h2>
                        @if ($text = $page->text('approach_text'))<p class="text-base text-gray-400 leading-relaxed md:max-w-[400px] md:ml-6 md:mt-0.5 md:shrink-0">{{ $text }}</p>@endif
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-14">
                        @foreach ($approach as $item)
                            <a href="{{ $item['url'] }}" class="relative group overflow-hidden rounded-2xl h-[400px] md:h-[500px] shadow-sm border border-gray-100 bg-gray-200 hover:no-underline">
                                @if ($item['image'])<img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" loading="lazy" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">@endif
                                <div class="absolute inset-x-0 bottom-0 p-6 md:p-8 bg-gradient-to-t from-black/90 via-black/40 to-transparent">
                                    <h3 class="text-xl md:text-3xl font-bold text-white leading-tight">{{ $item['title'] }}</h3>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($report = $page->report())
                    <div class="flex justify-center">
                        <a href="{{ $report['url'] }}" target="_blank" rel="noopener" class="flex items-center justify-center gap-2 w-fit rounded-md border border-primary bg-primary px-6 py-4 text-base font-semibold text-white shadow-sm hover:brightness-110 hover:no-underline">
                            {{ $report['label'] }} <x-theme::icon name="file-text" class="w-7 h-7" />
                        </a>
                    </div>
                @endif
            </div>
        </section>
    @endif

    <section class="csr-impact bg-[color-mix(in_srgb,var(--color-secondary)_55%,black)] py-16 pb-[72px]">
        <div class="text-center mb-12 px-5">
            <h2 class="text-2xl md:text-4xl font-bold text-white mb-3">{{ $page->text('impact_title') }}</h2>
            @if ($text = $page->text('impact_text'))<p class="text-base md:text-lg text-white/90 leading-relaxed max-w-5xl mx-auto mb-10">{{ $text }}</p>@endif
            <form method="get" action="{{ $page->listingUrl() }}#csr-stories" role="search" class="flex flex-col sm:flex-row items-center justify-center gap-4 container mx-auto px-4">
                <div class="relative w-full sm:flex-1">
                    <input type="search" name="q" value="{{ $page->search() }}" placeholder="{{ __('csr::csr.page.search_placeholder') }}" aria-label="{{ __('csr::csr.page.search_placeholder') }}"
                           class="w-full bg-white/5 text-white border border-white/10 rounded-full py-3 px-6 pl-12 outline-none focus:border-white/30 focus:bg-white/10 placeholder:text-white/40 text-sm">
                    <x-theme::icon name="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-[18px] h-[18px] text-white/40" />
                </div>
                @if (($options = $page->categoryOptions()) !== [])
                    <select name="category" onchange="this.form.submit()" aria-label="{{ __('csr::csr.page.all_categories') }}" class="w-full sm:w-auto sm:min-w-[400px] bg-white/5 text-white border border-white/10 rounded-full py-3 px-6 outline-none text-sm cursor-pointer">
                        <option value="" class="text-gray-900">{{ __('csr::csr.page.all_categories') }}</option>
                        @foreach ($options as $slug => $name)<option value="{{ $slug }}" class="text-gray-900" @selected($page->pickedSlug() === (string) $slug)>{{ $name }}</option>@endforeach
                    </select>
                @endif
            </form>
        </div>

        @if (($areas = $page->areas()) !== [])
            <div class="container mx-auto px-5">
                <div class="flex gap-5 overflow-x-auto snap-x snap-mandatory pb-2">
                    @foreach ($areas as $area)
                        <a href="{{ $area['url'] }}" @class(['snap-start shrink-0 w-[85%] md:w-[60%] lg:w-[44%] flex items-start justify-between gap-4 p-4 sm:p-5 sm:px-6 rounded-[10px] border transition-all duration-300 hover:no-underline',
                                                             'border-white/40 bg-white/10 ring-1 ring-white/10' => $area['current'], 'border-white/10 bg-white/5 hover:bg-white/10' => ! $area['current']])>
                            <span class="flex-1 min-w-0">
                                <span @class(['block text-lg sm:text-[24px] font-semibold mb-2 leading-tight', 'text-sky-400' => $area['current'], 'text-white' => ! $area['current']])>{{ $area['name'] }}</span>
                                @if ($area['text'])<span class="block text-sm text-white/90 leading-relaxed line-clamp-2">{{ $area['text'] }}</span>@endif
                            </span>
                            @if ($area['image'])<img src="{{ $area['image'] }}" alt="" loading="lazy" class="hidden sm:block w-[120px] md:w-[155px] h-[85px] md:h-[105px] object-cover rounded-lg shrink-0">@endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    <section id="csr-stories" class="bg-[color-mix(in_srgb,var(--color-secondary)_55%,black)] scroll-mt-32">
        <div class="container mx-auto px-5 pb-[72px]">
            <h2 class="text-2xl md:text-4xl font-extrabold text-white mb-1.5 leading-[1.2]">{{ $page->text('stories_title') }}</h2>
            @if ($text = $page->text('stories_text'))<p class="text-sm text-gray-300 mb-9">{{ $text }}</p>@endif
            @if (($cards = $page->stories()) === [])
                <p class="py-20 text-center text-gray-400">{{ __('csr::csr.page.none') }}</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-14">
                    @foreach ($cards as $story)
                        <article class="csr-story bg-lime-50 border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
                            <a href="{{ $story['url'] }}" class="block relative hover:no-underline">
                                @if ($story['image'])
                                    <img src="{{ $story['image'] }}" alt="{{ $story['title'] }}" loading="lazy" class="w-full h-[320px] object-cover block">
                                @else
                                    <div class="w-full h-[320px] bg-gradient-to-br from-secondary to-primary"></div>
                                @endif
                                <span class="absolute top-3 left-3 bg-white text-xs font-medium text-gray-700 px-2.5 py-1 rounded">{{ $story['date'] ?? __('csr::csr.page.ongoing') }}</span>
                            </a>
                            <div class="p-4 pb-5">
                                <a href="{{ $story['url'] }}" class="block hover:no-underline"><h3 class="text-xl sm:text-[24px] font-bold text-gray-900 leading-[1.35] mb-2.5 line-clamp-2 hover:text-primary">{{ $story['title'] }}</h3></a>
                                @if ($story['category'])<p class="text-sm text-gray-500">{{ $story['category'] }}</p>@endif
                                @if ($story['summary'])<p class="mt-1 text-base text-neutral-700 line-clamp-1">{{ $story['summary'] }}</p>@endif
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="rounded-xl bg-white px-4">{{ $stories->links('theme::partials.diu.pagination') }}</div>
            @endif
        </div>
    </section>
</x-theme::layout>
