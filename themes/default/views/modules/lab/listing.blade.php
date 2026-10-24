{{--
    DIU's lab pages. /labs (DIUFronend app/(public)/lab-facilities): hero "Laboratory Facilities", then each
    faculty with a card per department that has labs (View Labs). A department's /fsit/cse/labs
    (lab-facilities/[department]): hero with its name, the labs on the left, the first lab's page beside.
    Texts from Settings › Labs. Contract: $labs, $place (Department or null), $page (LabsPage)
--}}
<x-theme::layout :title="$place?->name ?? (settings('lab.listing_title') ?: __('lab::lab.page.laboratories'))">
    @if ($place)
        @include('theme::partials.diu.hero', [
            'title' => $place->name,
            'subtitle' => e($page->heroText()),
            'image' => media_url(settings('lab.listing_image'), 'large') ?? theme_asset('images/faculty-banner.jpg'),
            'breadcrumbs' => [
                ['label' => __('theme::theme.home'), 'url' => url('/')],
                ['label' => settings('lab.listing_title') ?: __('lab::lab.page.laboratories'), 'url' => url('/labs')],
                ['label' => $place->short_name ?: $place->name, 'url' => request()->url()],
            ],
        ])

        <main class="min-h-screen bg-white">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 lg:py-12">
                @if ($first = $page->first())
                    <div class="flex flex-col lg:flex-row gap-8">
                        <div class="lg:w-72 shrink-0">
                            @include('theme::partials.lab.sidebar', ['labs' => $first->siblings()])
                        </div>
                        <div class="flex-1 min-w-0">
                            @include('theme::partials.lab.details', ['lab' => $first])
                        </div>
                    </div>
                @else
                    <p class="text-gray-600 text-center py-16">{{ __('lab::lab.none') }}</p>
                @endif
            </div>
        </main>
    @else
        @include('theme::partials.diu.hero', [
            'title' => settings('lab.listing_title') ?: __('lab::lab.page.laboratories'),
            'subtitle' => e(settings('lab.listing_text')),
            'image' => media_url(settings('lab.listing_image'), 'large') ?? theme_asset('images/faculty-banner.jpg'),
            'breadcrumbs' => [['label' => __('theme::theme.home'), 'url' => url('/')], ['label' => settings('lab.listing_title') ?: __('lab::lab.page.laboratories'), 'url' => url('/labs')]],
        ])

        <main class="min-h-screen bg-white">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
                {{-- Beyond DIU: find a lab (FR-LAB-05) --}}
                <form method="get" class="flex flex-wrap justify-end gap-2 mb-10" role="search">
                    <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('lab::lab.search') }}" aria-label="{{ __('lab::lab.search') }}"
                           class="flex-1 sm:flex-none sm:w-72 px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-gray-900">
                    @if ($types->count() > 1)
                        <select name="type" aria-label="{{ __('lab::lab.fields.type') }}" class="px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
                            <option value="">{{ __('lab::lab.all_types') }}</option>
                            @foreach ($types as $t)<option value="{{ $t->slug() }}" @selected($type?->is($t))>{{ $t->name }}</option>@endforeach
                        </select>
                    @endif
                    <button type="submit" class="px-5 py-2 rounded-lg border border-gray-900 text-gray-900 text-sm font-medium hover:bg-gray-900 hover:text-white transition-colors cursor-pointer">{{ __('lab::lab.go') }}</button>
                </form>
                @if ($q !== '' && $labs->isNotEmpty())
                    {{-- A search lists the labs found --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-16">
                        @foreach ($labs as $found)
                            <a href="{{ $found->url() }}" class="group flex items-center justify-between border border-gray-200 rounded-lg px-5 py-4 text-gray-900 font-medium hover:border-gray-900 hover:no-underline transition-colors">
                                <span>{{ $found->name }}</span>
                                <x-theme::icon name="chevron-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                            </a>
                        @endforeach
                    </div>
                @endif
                <div class="space-y-16 lg:space-y-20">
                    @forelse ($page->groups() as $group)
                        <section class="space-y-8">
                            @if ($group['name'])
                                <div class="border-b border-gray-200 pb-4">
                                    <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $group['name'] }}</h2>
                                </div>
                            @endif
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                @foreach ($group['cards'] as $card)
                                    <a href="{{ $card['url'] }}" class="group block h-full hover:no-underline">
                                        <div class="border border-gray-200 rounded-lg overflow-hidden h-full flex flex-col hover:border-gray-900 transition-colors">
                                            <div class="aspect-[16/9] relative overflow-hidden bg-gray-50">
                                                @if ($card['image'])
                                                    <img src="{{ $card['image'] }}" alt="{{ $card['name'] }}" loading="lazy" class="w-full h-full object-cover">
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center bg-gray-50"><x-theme::icon name="building" class="w-16 h-16 text-gray-300" /></div>
                                                @endif
                                            </div>
                                            <div class="p-6 flex-1 flex flex-col">
                                                <h3 class="text-lg font-semibold text-gray-900 mb-3 leading-snug">{{ $card['name'] }}</h3>
                                                <p class="text-sm text-gray-600 line-clamp-3 mb-4 leading-relaxed flex-1">{{ $card['text'] }}</p>
                                                <div class="flex items-center text-gray-900 text-sm font-medium pt-4 border-t border-gray-100">
                                                    <span>{{ __('lab::lab.page.view_labs') }}@if ($card['count']) ({{ $card['count'] }})@endif</span>
                                                    <x-theme::icon name="chevron-right" class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" />
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @empty
                        <p class="text-gray-600 text-center">{{ __('lab::lab.none') }}</p>
                    @endforelse
                </div>
            </div>
        </main>
    @endif
</x-theme::layout>
