{{--
    /international: hero (DIU's "Your Global Education Journey Starts Here"); the numbers — partners, countries, conferences,
    visitors — as links; DIU's "Featured Partners" cards (logo, country flag, name, line); the international pages as links.
    Titles, lines and picture from Settings › International. Contract: the module's (counts, featured, base), $page (InternationalPage)
--}}
<x-theme::layout :title="$page->text('home_title') ?? __('international::international.title')">
    @include('theme::partials.diu.hero', [
        'title' => $page->text('home_title') ?? __('international::international.title'),
        'subtitle' => ($line = $page->text('home_text')) ? e($line) : null,
        'image' => $page->image('home') ?? theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => $page->breadcrumbs(),
    ])

    <div class="bg-gradient-to-br from-slate-50 to-blue-50">
        <div class="max-w-7xl mx-auto px-4 py-12 space-y-14">
            @if (($stats = $page->stats()) !== [])
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($stats as $stat)
                        <a href="{{ $stat['url'] }}" class="international-stat group rounded-2xl bg-white border border-slate-200 p-6 text-center hover:border-secondary hover:shadow-lg hover:no-underline transition">
                            <x-theme::icon :name="$stat['icon']" class="w-7 h-7 mx-auto mb-3 text-primary" />
                            <p class="text-4xl font-bold text-secondary">{{ number_format($stat['value']) }}</p>
                            <p class="mt-1 text-sm text-slate-600 group-hover:text-secondary">{{ $stat['label'] }}</p>
                        </a>
                    @endforeach
                </div>
            @endif

            @if (($featured = $page->featured()) !== [])
                <section>
                    <div class="text-center mb-10">
                        <h2 class="text-2xl md:text-4xl font-bold text-secondary">{{ __('international::international.featured') }}</h2>
                        <p class="mt-3 text-slate-600 max-w-3xl mx-auto">{{ __('international::international.page.featured_text') }}</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach ($featured as $u)
                            <a @if ($u['url']) href="{{ $u['url'] }}" target="_blank" rel="noopener" @endif class="featured-partner bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden group border border-gray-100 p-6 hover:no-underline">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="shrink-0 w-24 h-24 bg-gray-50 rounded-lg p-2 flex items-center justify-center border border-gray-100">
                                        @if ($u['logo'])<img src="{{ $u['logo'] }}" alt="{{ $u['name'] }}" loading="lazy" class="w-full h-full object-contain">@else<x-theme::icon name="building" class="w-10 h-10 text-gray-300" />@endif
                                    </div>
                                    @if ($u['country'])<span class="flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-full text-sm font-medium text-gray-700">@if ($u['flag_img'])<img src="{{ $u['flag_img'] }}" alt="" class="w-6 h-4 object-cover rounded">@endif{{ $u['country'] }}</span>@endif
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-secondary transition-colors">{{ $u['name'] }}</h3>
                                @if ($u['text'])<p class="text-gray-600 text-sm leading-relaxed mb-4 line-clamp-4">{{ $u['text'] }}</p>@endif
                                @if ($u['url'])<span class="flex items-center gap-2 text-secondary font-medium text-sm group-hover:gap-3 transition-all">{{ __('international::international.page.explore') }} <x-theme::icon name="arrow-right" class="w-4 h-4" /></span>@endif
                            </a>
                        @endforeach
                    </div>
                    <div class="mt-8 flex justify-center">
                        <x-theme::button :href="$page->url('partners')" variant="outlineSecondary" rounded="full" padding="px-6 py-3" size="text-base md:text-lg">{{ $page->text('partners_title') ?? __('international::international.partners') }}</x-theme::button>
                    </div>
                </section>
            @endif

            @if (($side = $page->sideLinks()) !== [])
                <section>
                    <h2 class="text-xl font-bold text-neutral-700 mb-4">{{ __('international::international.page.browse') }}</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach ($side as $link)
                            <a href="{{ $link['url'] }}" class="flex h-[72px] items-center gap-2.5 rounded-[10px] bg-slate-100 px-4 text-lg text-neutral-600 hover:bg-slate-200 hover:no-underline transition"><x-theme::icon name="arrow-up-right" class="w-5 h-5 text-secondary shrink-0" />{{ $link['label'] }}</a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-theme::layout>
