{{--
    DIU /faculties (DIUFronend app/(public)/faculties/page.jsx): hero "Explore DIU's Faculties"; "Browse by
    Section" with the tabs Faculties / Deans / Department Heads, then Settings › Faculties: its links (Faculty
    Members …), Quick Links, and the title, text and picture on top; a card per faculty, the deans and the department heads by
    faculty (people of the faculties and departments); then Essential Campus Services.
    Contract: $faculties (Collection<Faculty>)
--}}
<x-theme::layout :title="settings('faculty.listing_title') ?: __('theme::theme.faculties.title')">
    @include('theme::partials.diu.hero', [
        'title' => settings('faculty.listing_title') ?: __('theme::theme.faculties.title'),
        'subtitle' => e(settings('faculty.listing_text') ?: __('theme::theme.faculties.subtitle')),
        'image' => media_url(settings('faculty.listing_image'), 'large') ?? theme_asset('images/faculty-banner.webp'),
        'breadcrumbs' => [['label' => __('theme::theme.home'), 'url' => url('/')], ['label' => __('theme::theme.faculties.crumb'), 'url' => url('/faculties')]],
    ])

    <div class="min-h-screen bg-gray-50" x-data="{ current: 'faculties', more: false }">
        <div class="container mx-auto px-6 py-12">
            <div class="flex flex-col lg:flex-row gap-12">
                @include('theme::partials.diu.browse-sidebar', [
                    'items' => [
                        ['id' => 'faculties', 'label' => __('theme::theme.faculties.tab_faculties'), 'url' => null],
                        ['id' => 'deans', 'label' => __('theme::theme.faculties.tab_deans'), 'url' => null],
                        ['id' => 'heads', 'label' => __('theme::theme.faculties.tab_heads'), 'url' => null],
                        ...collect(link_list(settings('faculty.side_links')))->map(fn ($link, $i) => ['id' => 'link-'.$i, ...$link])->all(),   // Settings › Faculties
                    ],
                    'quickLinks' => link_list(settings('faculty.quick_links')),
                ])

                {{-- Faculties --}}
                <div class="flex-1" x-show="current === 'faculties'">
                    <div class="mb-10">
                        <x-theme::heading tag="h2">{{ __('theme::theme.faculties.browse_faculties') }}</x-theme::heading>
                        <p class="text-gray-600 text-base">{{ __('theme::theme.faculties.browse_faculties_text') }}</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        @forelse ($faculties as $faculty)
                            <div class="bg-neutral-100 p-3 md:p-6 rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300">
                                <div class="relative pb-4 aspect-[16/10]">
                                    @if ($photo = $faculty->firstMedia('photo') ?? $faculty->firstMedia('cover'))
                                        <img src="{{ $photo->url('medium') ?? $photo->url() }}" alt="{{ $faculty->name }}" loading="lazy" class="rounded-xl w-full h-[220px] object-cover">
                                    @else
                                        <div class="rounded-xl w-full h-[220px] bg-gray-200"></div>
                                    @endif
                                </div>
                                <a href="{{ url($faculty->url()) }}" class="hover:no-underline">
                                    <h3 class="text-[24px] font-semibold text-neutral-800 mb-4 leading-tight hover:text-secondary transition-colors hover:underline">{{ $faculty->name }}</h3>
                                </a>
                                <p class="text-base text-neutral-500 mb-6 leading-relaxed line-clamp-4">{{ trim(html_entity_decode(strip_tags((string) $faculty->overview))) }}</p>
                                <div class="bg-gray-200 mb-3 w-full h-[0.5px]"></div>
                                <a href="{{ url($faculty->url()) }}" class="flex items-center gap-2 text-secondary font-medium text-[24px] hover:text-sky-700 hover:no-underline transition-colors group">
                                    {{ __('theme::theme.faculties.learn_more') }}
                                    <x-theme::icon name="arrow-up-right" class="w-4 h-4 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
                                </a>
                            </div>
                        @empty
                            <div class="col-span-full text-center py-12"><p class="text-gray-500">{{ __('theme::theme.faculties.none') }}</p></div>
                        @endforelse
                    </div>
                </div>

                {{-- Deans: by faculty --}}
                <div class="flex-1" x-show="current === 'deans'" x-cloak>
                    <div class="mb-10">
                        <x-theme::heading tag="h2">{{ __('theme::theme.faculties.browse_deans') }}</x-theme::heading>
                        <p class="text-gray-600 text-base">{{ __('theme::theme.faculties.browse_deans_text') }}</p>
                    </div>
                    @foreach ($faculties as $faculty)
                        @if (($deans = $faculty->people(['dean', 'associate_dean']))->isNotEmpty())
                            <h2 class="text-2xl border-b border-gray-200 pb-2 font-semibold text-gray-800 mb-4 mt-8">{{ $faculty->name }}</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                @foreach ($deans as $person)
                                    @include('theme::partials.faculty.person-card', ['person' => $person, 'line' => $faculty->name.' ('.__('theme::theme.faculties.role_'.$person->roleKey()).')'])
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                    @if ($faculties->every(fn ($f) => $f->people(['dean', 'associate_dean'])->isEmpty()))
                        <div class="text-center py-12"><p class="text-gray-500">{{ __('theme::theme.faculties.no_deans') }}</p></div>
                    @endif
                </div>

                {{-- Department Heads: by faculty --}}
                <div class="flex-1" x-show="current === 'heads'" x-cloak>
                    <div class="mb-10">
                        <x-theme::heading tag="h2">{{ __('theme::theme.faculties.browse_heads') }}</x-theme::heading>
                        <p class="text-gray-600 text-base">{{ __('theme::theme.faculties.browse_heads_text') }}</p>
                    </div>
                    @foreach ($faculties as $faculty)
                        @if ($faculty->departments->contains(fn ($d) => $d->people('head')->isNotEmpty()))
                            <h2 class="text-2xl border-b border-gray-200 pb-2 font-semibold text-gray-800 mb-4 mt-8">{{ $faculty->name }}</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                @foreach ($faculty->departments as $department)
                                    @foreach ($department->people('head') as $person)
                                        @include('theme::partials.faculty.person-card', ['person' => $person, 'line' => $department->name])
                                    @endforeach
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                    @if ($faculties->every(fn ($f) => ! $f->departments->contains(fn ($d) => $d->people('head')->isNotEmpty())))
                        <div class="text-center py-12"><p class="text-gray-500">{{ __('theme::theme.faculties.no_heads') }}</p></div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('theme::partials.diu.service-cards', [
        'heading' => __('theme::theme.faculties.services_heading'),
        'subheading' => __('theme::theme.faculties.services_text'),
        'cards' => [
            ['image' => ['url' => theme_asset('images/services/es-3.webp')], 'title' => __('theme::theme.faculties.service_transport'), 'text' => __('theme::theme.faculties.service_transport_text'), 'url' => url('/transport')],
            ['image' => ['url' => theme_asset('images/services/es-4.webp')], 'title' => __('theme::theme.faculties.service_hostel'), 'text' => __('theme::theme.faculties.service_hostel_text'), 'url' => 'https://hall.daffodilvarsity.edu.bd/'],
            ['image' => ['url' => theme_asset('images/services/es-2.webp')], 'title' => __('theme::theme.faculties.service_scholarships'), 'text' => __('theme::theme.faculties.service_scholarships_text'), 'url' => url('/scholarships')],
            ['image' => ['url' => theme_asset('images/services/es-1.webp')], 'title' => __('theme::theme.faculties.service_life'), 'text' => __('theme::theme.faculties.service_life_text'), 'url' => url('/campus-life')],
        ],
    ])
</x-theme::layout>
