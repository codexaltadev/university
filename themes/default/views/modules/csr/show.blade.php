{{--
    /csr/{slug} as DIU's /csr-activities/{slug}: hero with the title and "CSR Activity Detail"; the title, date and category, the
    picture, the summary, the text; the video, the pictures, the outside link; back to every activity.
    Contract: the module's (activity, gallery, base), $page (CsrPage)
--}}
<x-theme::layout :title="$activity->title">
    @include('theme::partials.diu.hero', [
        'title' => (string) $activity->title,
        'subtitle' => e(__('csr::csr.page.detail')),
        'image' => $page->image() ?? theme_image('page_hero_image', 'images/page-hero.jpg'),
        'breadcrumbs' => $page->breadcrumbs((string) $activity->title),
    ])

    @if ($a = $page->activity())
        <div class="bg-white">
            <article class="max-w-5xl mx-auto px-4 py-12">
                <h1 class="text-xl md:text-4xl font-bold text-neutral-800 leading-[1.35] mb-2">{{ $a['title'] }}</h1>
                <p class="flex flex-wrap items-center gap-3 text-base text-gray-500 mb-6">
                    @if ($a['date'])<span class="inline-flex items-center gap-1.5"><x-theme::icon name="calendar" class="w-4 h-4" />{{ $a['date'] }}</span>@endif
                    @if ($a['category'])<span class="inline-flex px-2.5 py-1 bg-gray-100 rounded text-sm text-gray-700">{{ $a['category'] }}</span>@endif
                </p>
                @if ($a['image'])<img src="{{ $a['image'] }}" alt="{{ $a['title'] }}" class="w-full max-h-[555px] object-cover rounded mb-8">@endif
                @if ($a['summary'])<p class="text-lg text-gray-700 mb-6">{{ $a['summary'] }}</p>@endif
                <div class="prose max-w-none">{!! $a['body'] !!}</div>

                @if ($a['video'])
                    <div class="relative aspect-video rounded-xl overflow-hidden bg-black mt-8"><iframe src="{{ $a['video'] }}" title="{{ $a['title'] }}" loading="lazy" allowfullscreen class="absolute inset-0 w-full h-full border-0"></iframe></div>
                @elseif ($a['video_url'])
                    <p class="mt-8"><x-theme::button :href="$a['video_url']" target="_blank" variant="outlineSecondary" rounded="full" padding="px-5 py-2.5" size="text-base"><x-theme::icon name="play" class="w-4 h-4" /> {{ __('csr::csr.watch') }}</x-theme::button></p>
                @endif

                @if ($a['gallery'] !== [])
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mt-8">
                        @foreach ($a['gallery'] as $photo)
                            <a href="{{ $photo['url'] }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-lg"><img src="{{ $photo['thumb'] }}" alt="{{ $photo['alt'] }}" loading="lazy" class="w-full aspect-[4/3] object-cover hover:scale-105 transition-transform"></a>
                        @endforeach
                    </div>
                @endif

                <div class="flex flex-wrap gap-3 mt-10 pt-6 border-t border-gray-100">
                    @if ($a['external'])<x-theme::button :href="$a['external']" target="_blank" variant="outlineSecondary" rounded="full" padding="px-5 py-2.5" size="text-base">{{ __('csr::csr.page.more') }} <x-theme::icon name="arrow-up-right" class="w-4 h-4" /></x-theme::button>@endif
                    <x-theme::button :href="$page->listingUrl()" variant="primary" rounded="full" padding="px-5 py-2.5" size="text-base"><x-theme::icon name="arrow-left" class="w-4 h-4" /> {{ __('csr::csr.page.all') }}</x-theme::button>
                </div>
            </article>
        </div>
    @endif
</x-theme::layout>
