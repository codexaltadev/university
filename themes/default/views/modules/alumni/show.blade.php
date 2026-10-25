{{--
    /alumni/{slug} — a graduate's page in the alumni cards' look (DIU has none of its own): hero with the name; the 3:4 photo
    with "Pride", the study facts and contacts on the left; on the right what they do and where, the video and the story.
    Contract: the module's (alumnus, person), $page (AlumniPage)
--}}
<x-theme::layout :title="$person->name()">
    @include('theme::partials.diu.hero', [
        'title' => $person->name(),
        'subtitle' => ($headline = $alumnus->headline()) ? e($headline) : null,
        'image' => $page->image() ?? theme_image('page_hero_image', 'images/page-hero.jpg'),
        'breadcrumbs' => $page->breadcrumbs($person->name()),
    ])

    @if ($p = $page->profile())
        <div class="bg-white">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-12 lg:py-16 grid lg:grid-cols-3 gap-10">
                <aside class="space-y-6">
                    <div class="relative aspect-[3/4] bg-gray-100 overflow-hidden rounded-xl">
                        @if ($p['photo'])
                            <img src="{{ $p['photo'] }}" alt="{{ $p['name'] }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full bg-gradient-to-br from-secondary to-primary flex items-center justify-center" aria-hidden="true"><span class="text-7xl font-bold text-white/90">{{ $p['initials'] }}</span></div>
                        @endif
                        @if ($p['pride'])
                            <span class="absolute top-4 left-0 bg-white py-1.5 px-2.5 text-sm font-semibold text-neutral-800 flex items-center gap-1.5"><x-theme::icon name="sparkles" class="w-4 h-4 text-amber-500" />{{ __('alumni::alumni.pride_badge') }}</span>
                        @endif
                    </div>
                    @if ($p['study'] !== [])
                        <dl class="rounded-xl border border-gray-200 divide-y divide-gray-100">
                            @foreach ($p['study'] as $fact)
                                <div class="flex justify-between gap-4 px-5 py-3 text-sm">
                                    <dt class="text-gray-500">{{ $fact['label'] }}</dt>
                                    <dd class="text-right font-medium text-gray-900">@if ($fact['url'])<a href="{{ $fact['url'] }}" class="text-primary">{{ $fact['value'] }}</a>@else{{ $fact['value'] }}@endif</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                    @if ($p['email'] || $p['phone'] || $p['address'] || $p['social'] !== [])
                        <div class="rounded-xl bg-gray-50 p-5 space-y-3 text-sm">
                            <h2 class="text-base font-semibold text-gray-900">{{ __('alumni::alumni.page.contact') }}</h2>
                            @if ($p['email'])<p class="flex items-center gap-2 min-w-0"><x-theme::icon name="mail" class="w-4 h-4 text-gray-400 shrink-0" /><a href="mailto:{{ $p['email'] }}" class="text-primary break-all">{{ $p['email'] }}</a></p>@endif
                            @if ($p['phone'])<p class="flex items-center gap-2"><x-theme::icon name="phone" class="w-4 h-4 text-gray-400 shrink-0" />{{ $p['phone'] }}</p>@endif
                            @if ($p['address'])<p class="flex items-start gap-2"><x-theme::icon name="map-pin" class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" />{{ $p['address'] }}</p>@endif
                            @if ($p['social'] !== [])
                                <p class="flex gap-2 pt-1">
                                    @foreach ($p['social'] as $link)
                                        <a href="{{ $link['url'] }}" target="_blank" rel="noopener nofollow" aria-label="{{ $link['label'] }}" class="w-9 h-9 rounded-full bg-white border border-gray-200 flex items-center justify-center text-secondary hover:bg-secondary hover:text-white"><x-theme::icon :name="$link['icon']" class="w-4 h-4" /></a>
                                    @endforeach
                                </p>
                            @endif
                        </div>
                    @endif
                </aside>

                <main class="lg:col-span-2 space-y-8">
                    <div class="space-y-3">
                        @if ($p['designation'])<p class="flex items-start gap-2 text-xl text-gray-800"><x-theme::icon name="briefcase" class="w-5 h-5 text-gray-400 shrink-0 mt-1" />{{ $p['designation'] }}</p>@endif
                        @if ($p['organization'])<p class="flex items-start gap-2 text-lg text-gray-600"><x-theme::icon name="building" class="w-5 h-5 text-gray-400 shrink-0 mt-1" />{{ $p['organization'] }}</p>@endif
                    </div>
                    @if ($p['video'])
                        <div class="relative aspect-video rounded-xl overflow-hidden bg-black">
                            <iframe src="{{ $p['video'] }}" title="{{ __('alumni::alumni.video') }}" loading="lazy" allowfullscreen class="absolute inset-0 w-full h-full border-0"></iframe>
                        </div>
                    @endif
                    @if ($p['story'])
                        <section>
                            <h2 class="text-2xl font-bold text-gray-900 mb-4">{{ __('alumni::alumni.fields.story') }}</h2>
                            <div class="prose max-w-none text-gray-700">{!! $p['story'] !!}</div>
                        </section>
                    @endif
                    <p><a href="{{ $page->listingUrl() }}" class="inline-flex items-center gap-2 text-primary font-medium"><x-theme::icon name="arrow-left" class="w-4 h-4" />{{ __('alumni::alumni.all_alumni') }}</a></p>
                </main>
            </div>
        </div>
    @endif
</x-theme::layout>
