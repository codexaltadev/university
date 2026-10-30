{{--
    /international/partners as DIU's /international-linkage (DIUFronend app/(public)/international-linkage): hero "International
    Linkage"; on the left "Browse by Section" (the international pages) and Quick Links; on the right a white card "Select Country
    to View Universities" — the countries with their universities, the chosen one as a navy → blue chip — then a card per
    university (logo, name, country, Visit Website) and DIU pagination; "By country": a card per country.
    Titles, lines, picture and links from Settings › International.
    Contract: the module's (partners, countries, perCountry, countryCounts, country, type, q, layout, base), $page (InternationalPage)
--}}
<x-theme::layout :title="$page->text('partners_title') ?? __('international::international.partners')">
    @include('theme::partials.diu.hero', [
        'title' => $page->text('partners_title') ?? __('international::international.partners'),
        'subtitle' => ($line = $page->text('partners_text')) ? e($line) : null,
        'image' => $page->image('partners') ?? theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => $page->breadcrumbs($page->text('partners_title') ?? __('international::international.partners'), 'partners'),
    ])

    <div class="min-h-screen bg-white" x-data="{ more: false }">
        <div class="container mx-auto px-6 py-12">
            <div class="flex flex-col lg:flex-row gap-12">
                @if (($side = $page->sideLinks()) !== [] || $page->quickLinks() !== [])
                    @include('theme::partials.diu.browse-sidebar', ['items' => $side, 'quickLinks' => $page->quickLinks(), 'width' => 'lg:w-[340px]'])
                @endif

                <div class="flex-1 min-w-0">
                    <div class="bg-white/90 rounded-3xl shadow border border-slate-100 p-6 sm:p-8 mb-12">
                        <h2 class="text-xl sm:text-2xl font-bold text-secondary mb-5">{{ __('international::international.page.select_country') }}</h2>
                        <form method="get" action="{{ $page->url('partners') }}" class="flex flex-col sm:flex-row gap-3">
                            <select name="country" onchange="this.form.submit()" aria-label="{{ __('international::international.page.select_country') }}" class="flex-1 rounded-xl border border-slate-200 bg-white px-4 py-3 text-base sm:text-lg text-slate-700">
                                <option value="">{{ __('international::international.page.all_countries') }}</option>
                                @foreach ($page->countries() as $c)
                                    <option value="{{ $c['value'] }}" @selected($c['current'])>{{ $c['label'] }} ({{ trans_choice('international::international.page.universities', $c['count'], ['count' => $c['count']]) }})</option>
                                @endforeach
                            </select>
                            <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('international::international.page.search') }}" aria-label="{{ __('international::international.page.search') }}" class="sm:w-64 rounded-xl border border-slate-200 bg-white px-4 py-3 text-base text-slate-700">
                            <noscript><button type="submit" class="rounded-xl bg-secondary px-5 py-3 text-white">{{ __('international::international.page.select_country') }}</button></noscript>
                        </form>
                        @if ($chosen = $page->chosen())
                            <div class="mt-6 inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-secondary to-primary text-white rounded-2xl">
                                @if ($chosen['flag_img'])<img loading="lazy" decoding="async" src="{{ $chosen['flag_img'] }}" alt="" class="w-6 h-4 object-cover rounded">@endif
                                <span class="font-semibold">{{ $chosen['label'] }}</span>
                                <span class="bg-white/20 px-2 py-1 rounded-lg text-sm">{{ trans_choice('international::international.page.universities', $chosen['total'], ['count' => $chosen['total']]) }}</span>
                            </div>
                        @endif
                    </div>

                    @if ($partners === null)
                        {{-- "By country": a card per country --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4">
                            @foreach ($page->countries() as $c)
                                <a href="{{ $page->url('partners', ['country' => $c['value']]) }}" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 hover:border-secondary hover:shadow-md hover:no-underline transition">
                                    @if ($c['flag_img'])<img loading="lazy" decoding="async" src="{{ $c['flag_img'] }}" alt="" class="w-10 h-7 object-cover rounded shrink-0">@else<span class="text-3xl leading-none" aria-hidden="true">{{ $c['flag'] }}</span>@endif
                                    <span class="min-w-0"><span class="block font-semibold text-slate-800 truncate">{{ $c['label'] }}</span><span class="text-sm text-slate-500">{{ trans_choice('international::international.page.universities', $c['count'], ['count' => $c['count']]) }}</span></span>
                                </a>
                            @endforeach
                        </div>
                    @elseif (($universities = $page->universities()) === [])
                        <p class="text-center py-20 text-slate-600">{{ __('international::international.none') }}</p>
                    @elseif ($layout === 'table')
                        {{-- Layout "table" (Appearance › Layouts or ?view=table) --}}
                        <div class="overflow-x-auto bg-white rounded-2xl shadow-lg border border-gray-200 mb-12">
                            <table class="w-full text-sm">
                                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200 text-left">
                                    <tr><th scope="col" class="px-6 py-4 font-semibold text-gray-900">{{ __('international::international.fields.name') }}</th><th scope="col" class="px-6 py-4 font-semibold text-gray-900">{{ __('international::international.fields.country') }}</th><th scope="col" class="px-6 py-4 font-semibold text-gray-900">{{ __('international::international.page.visit') }}</th></tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($universities as $u)
                                        <tr class="hover:bg-gray-50"><td class="px-6 py-3 font-medium text-gray-900">{{ $u['name'] }}</td><td class="px-6 py-3 text-gray-700">{{ $u['country'] }}</td><td class="px-6 py-3">@if ($u['url'])<a href="{{ $u['url'] }}" target="_blank" rel="noopener" class="text-primary">{{ parse_url($u['url'], PHP_URL_HOST) }}</a>@endif</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $partners->links('theme::partials.diu.pagination') }}
                    @else
                        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8 mb-12">
                            @foreach ($universities as $u)
                                <div class="university-card group relative w-full">
                                    <div class="absolute -inset-0.5 bg-gradient-to-r from-slate-300 via-gray-300 to-slate-300 rounded-2xl blur opacity-20 group-hover:opacity-30 transition-opacity duration-300"></div>
                                    <div class="relative bg-white rounded-2xl shadow-lg border border-white/50 overflow-hidden group-hover:shadow-xl transition-all duration-300 hover:scale-[1.02] h-full flex flex-col">
                                        <div class="h-48 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center p-6">
                                            @if ($u['logo'])<img src="{{ $u['logo'] }}" alt="{{ $u['name'] }}" loading="lazy" class="max-w-full max-h-full object-contain">@else<x-theme::icon name="building" class="w-20 h-20 text-gray-400" />@endif
                                        </div>
                                        <div class="p-6 flex flex-col justify-between flex-1 gap-4">
                                            <div>
                                                <h3 class="font-bold text-xl text-slate-800 mb-3 group-hover:text-secondary transition-colors line-clamp-2">{{ $u['name'] }}</h3>
                                                @if ($u['country'])<p class="flex items-center gap-2 text-slate-600 text-sm"><x-theme::icon name="map-pin" class="w-4 h-4" />@if ($u['flag_img'])<img loading="lazy" decoding="async" src="{{ $u['flag_img'] }}" alt="" class="w-5 h-3.5 object-cover rounded-sm">@endif{{ $u['country'] }}</p>@endif
                                                @if ($u['agreement'] || $u['expired'])
                                                    <p class="mt-2 flex flex-wrap gap-2">
                                                        @if ($u['agreement'])<span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary">{{ $u['agreement'] }}</span>@endif
                                                        @if ($u['expired'])<span class="rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-red-700">{{ __('international::international.expired') }}</span>@endif
                                                    </p>
                                                @endif
                                            </div>
                                            @if ($u['url'])
                                                <a href="{{ $u['url'] }}" target="_blank" rel="noopener" class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl font-medium bg-gradient-to-r from-secondary to-primary text-white hover:from-primary hover:to-secondary shadow-lg hover:no-underline transition-all">
                                                    {{ __('international::international.page.visit') }} <x-theme::icon name="external" class="w-4 h-4" />
                                                </a>
                                            @else
                                                <span class="w-full flex items-center justify-center px-4 py-3 rounded-xl font-medium bg-gray-200 text-gray-500">{{ __('international::international.page.visit') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        {{ $partners->links('theme::partials.diu.pagination') }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-theme::layout>
