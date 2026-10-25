{{--
    /administration/{slug} as DIU's /admin/{section}/{id}: hero and the sections on the left as on the directory (this
    person's section chosen); a blue card with Back, the photo, name, designation and office; the contacts as soft
    boxes; then the biography and every current public role. Contract: the module's (member, person, office, places,
    categories, counts), $page (AdministrationPage)
--}}
<x-theme::layout :title="$person->name()">
    @include('theme::partials.diu.hero', [
        'title' => $page->title(),
        'subtitle' => null,
        'image' => $page->image() ?? theme_image('page_hero_image', 'images/page-hero.jpg'),
        'breadcrumbs' => [['label' => __('theme::theme.home'), 'url' => url('/')], ['label' => $page->title(), 'url' => $page->listingUrl()], ['label' => $person->name(), 'url' => url($member->url())]],
    ])

    <div class="min-h-screen bg-white" x-data="{ more: false }">
        <div class="container mx-auto px-6 py-12">
            <div class="flex flex-col lg:flex-row gap-12">
                @include('theme::partials.diu.browse-sidebar', ['items' => $page->sections(), 'quickLinks' => $page->quickLinks()])

                @if ($p = $page->profile())
                    <main class="administration-profile flex-1 min-w-0">
                        <div class="rounded-xl bg-gradient-to-r from-primary to-blue-600 text-white">
                            <div class="px-6 sm:px-8 py-8">
                                <a href="{{ $page->backUrl() }}" class="inline-flex items-center gap-2 mb-6 text-blue-100 hover:text-white hover:no-underline transition-colors">
                                    <x-theme::icon name="chevron-left" class="w-5 h-5" /><span>{{ __('administration::administration.page.back') }}</span>
                                </a>
                                <div class="flex flex-col lg:flex-row lg:items-end gap-8">
                                    <div class="w-32 h-32 lg:w-40 lg:h-40 rounded-2xl overflow-hidden border-4 border-white/20 shadow-2xl mx-auto lg:mx-0 shrink-0 bg-white/10">
                                        @if ($p['photo'])
                                            <img src="{{ $p['photo'] }}" alt="{{ $p['name'] }}" class="w-full h-full object-cover">
                                        @else
                                            <span class="w-full h-full flex items-center justify-center" aria-hidden="true"><x-theme::icon name="user" class="w-16 h-16 text-white/70" /></span>
                                        @endif
                                    </div>
                                    <div class="flex-1 text-center lg:text-left">
                                        <h1 class="text-3xl lg:text-4xl font-bold mb-2 text-white">{{ $p['name'] }}</h1>
                                        @if ($p['designation'])
                                            <p class="flex items-center justify-center lg:justify-start gap-2 mb-3 text-xl text-blue-100"><x-theme::icon name="building" class="w-5 h-5 text-blue-200" />{{ $p['designation'] }}</p>
                                        @endif
                                        <p class="flex items-center justify-center lg:justify-start gap-2 text-blue-200">
                                            <x-theme::icon name="graduation-cap" class="w-4 h-4" />
                                            @if ($p['office_url'])<a href="{{ $p['office_url'] }}" class="text-blue-100 hover:text-white">{{ $p['office'] }}</a>@else{{ $p['office'] ?: $p['site'] }}@endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($p['email'] || $p['phone'])
                            <div class="grid sm:grid-cols-2 gap-4 mt-6">
                                @if ($p['email'])
                                    <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg min-w-0">
                                        <span class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center shrink-0"><x-theme::icon name="mail" class="w-6 h-6 text-primary" /></span>
                                        <div class="min-w-0"><h2 class="text-lg font-semibold text-gray-900 mb-1">{{ __('administration::administration.page.email') }}</h2><a href="mailto:{{ $p['email'] }}" class="text-primary hover:underline break-all">{{ $p['email'] }}</a></div>
                                    </div>
                                @endif
                                @if ($p['phone'])
                                    <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg min-w-0">
                                        <span class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center shrink-0"><x-theme::icon name="phone" class="w-6 h-6 text-primary" /></span>
                                        <div class="min-w-0"><h2 class="text-lg font-semibold text-gray-900 mb-1">{{ __('administration::administration.page.phone') }}</h2><a href="tel:{{ $p['tel'] }}" class="text-gray-700 hover:text-primary">{{ $p['phone'] }}</a>@if ($p['extension'])<span class="text-gray-500"> · {{ __('administration::administration.ext') }} {{ $p['extension'] }}</span>@endif</div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if ($p['biography'])
                            <section class="mt-10">
                                <h2 class="text-2xl font-bold text-gray-900 mb-4">{{ __('administration::administration.fields.biography') }}</h2>
                                <div class="prose max-w-none text-gray-700">{!! $p['biography'] !!}</div>
                            </section>
                        @endif

                        @if (($places = $page->places()) !== [])
                            <section class="mt-10">
                                <h2 class="text-2xl font-bold text-gray-900 mb-4">{{ __('administration::administration.sections.places') }}</h2>
                                <ul class="divide-y divide-gray-100 border border-gray-200 rounded-xl">
                                    @foreach ($places as $place)
                                        <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-4">
                                            @if ($place['url'])<a href="{{ $place['url'] }}" class="font-semibold text-primary">{{ $place['place'] }}</a>@else<span class="font-semibold text-gray-900">{{ $place['place'] }}</span>@endif
                                            <span class="text-sm text-gray-600 bg-gray-100 rounded-full px-3 py-1">{{ $place['role'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </section>
                        @endif
                    </main>
                @endif
            </div>
        </div>
    </div>
</x-theme::layout>
