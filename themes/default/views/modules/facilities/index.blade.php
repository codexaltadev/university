{{--
    /facilities and /{entity}/facilities: hero; the categories as pills; a picture card per facility as DIU's program page shows
    them (picture, title, two lines, round arrow) — its page, or its outside address (DIU's library, hostel, laptop site …).
    Title, line and picture from Settings › Facilities. Contract: the module's (facilities, categories, category, layout, base), $page (FacilitiesPage)
--}}
<x-theme::layout :title="$page->title()">
    @include('theme::partials.diu.hero', [
        'title' => $page->title(),
        'subtitle' => $page->text() ? e($page->text()) : null,
        'image' => $page->image() ?? theme_image('page_hero_image', 'images/page-hero.jpg'),
        'breadcrumbs' => $page->breadcrumbs(),
    ])

    <div class="min-h-screen bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            @if (($categories = $page->categories()) !== [])
                <nav class="flex flex-wrap gap-2 mb-8" aria-label="{{ __('facilities::facilities.fields.category') }}">
                    @foreach ($categories as $c)
                        <a href="{{ $c['url'] }}" @if ($c['current']) aria-current="page" @endif
                           @class(['px-5 py-2.5 rounded-full text-sm font-medium border transition-colors hover:no-underline', 'bg-secondary text-white border-secondary' => $c['current'], 'bg-white text-slate-700 border-slate-200 hover:border-secondary hover:text-secondary' => ! $c['current']])>{{ $c['label'] }}</a>
                    @endforeach
                </nav>
            @endif

            @if (($cards = $page->cards()) === [])
                <p class="text-center py-16 text-gray-500">{{ __('facilities::facilities.none') }}</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach ($cards as $card)
                        <a href="{{ $card['url'] ?? '#' }}" @if ($card['external']) target="_blank" rel="noopener" @endif
                           class="facility-card group relative isolate flex aspect-[4/5] w-full overflow-hidden rounded-[20px] bg-black shadow-xl ring-1 ring-black/30 transition-transform hover:-translate-y-0.5 hover:no-underline">
                            <img src="{{ $card['image'] ?? theme_asset('images/program/facility.jpg') }}" alt="{{ $card['title'] }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover object-center transition-transform duration-500 group-hover:scale-110">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/25 to-black/0"></div>
                            <div class="relative mt-auto z-10 flex w-full items-end justify-between p-5">
                                <div class="max-w-[80%] text-white">
                                    <h3 class="text-[18px] md:text-[20px] font-semibold leading-tight">{{ $card['title'] }}</h3>
                                    @if ($card['text'])<p class="mt-2 text-[14px] md:text-[15px] text-white/90 leading-snug line-clamp-2">{{ $card['text'] }}</p>@endif
                                </div>
                                <span class="ml-3 grid size-10 place-items-center shrink-0 rounded-full text-white backdrop-blur-sm ring-1 ring-white/30 transition-colors group-hover:bg-white group-hover:text-zinc-900">
                                    <x-theme::icon :name="$card['external'] ? 'arrow-up-right' : 'chevron-right'" class="h-4 w-4" />
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-theme::layout>
