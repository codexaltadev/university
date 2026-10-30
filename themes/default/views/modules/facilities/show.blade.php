{{--
    /facilities/{slug}: hero with the facility's name; its picture, summary, text, pictures and outside address.
    Contract: the module's (facility, gallery, base), $page (FacilitiesPage)
--}}
<x-theme::layout :title="$facility->title">
    @include('theme::partials.diu.hero', [
        'title' => (string) $facility->title,
        'subtitle' => null,
        'image' => $page->image() ?? theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => $page->breadcrumbs((string) $facility->title),
    ])

    @if ($f = $page->facility())
        <div class="bg-white">
            <article class="max-w-5xl mx-auto px-4 py-12">
                @if ($f['image'])<img src="{{ $f['image'] }}" alt="{{ $f['title'] }}" class="w-full max-h-[520px] object-cover rounded-[20px] mb-8">@endif
                @if ($f['summary'])<p class="text-lg text-gray-700 mb-6">{{ $f['summary'] }}</p>@endif
                <div class="prose max-w-none">{!! $f['body'] !!}</div>
                @if ($f['gallery'] !== [])
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mt-8">
                        @foreach ($f['gallery'] as $photo)
                            <a href="{{ $photo['url'] }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-lg"><img src="{{ $photo['thumb'] }}" alt="{{ $photo['alt'] }}" loading="lazy" class="w-full aspect-[4/3] object-cover hover:scale-105 transition-transform"></a>
                        @endforeach
                    </div>
                @endif
                <div class="flex flex-wrap gap-3 mt-10 pt-6 border-t border-gray-100">
                    @if ($f['external'])<x-theme::button :href="$f['external']" target="_blank" variant="outlineSecondary" rounded="full" padding="px-5 py-2.5" size="text-base">{{ __('facilities::facilities.more') }} <x-theme::icon name="arrow-up-right" class="w-4 h-4" /></x-theme::button>@endif
                    <x-theme::button :href="$page->listingUrl()" variant="primary" rounded="full" padding="px-5 py-2.5" size="text-base"><x-theme::icon name="arrow-left" class="w-4 h-4" /> {{ $page->title() }}</x-theme::button>
                </div>
            </article>
        </div>
    @endif
</x-theme::layout>
