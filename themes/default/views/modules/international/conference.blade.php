{{--
    /international/conferences/{slug} as DIU's /conferences/{category}/{id}: hero with the title; the picture, the date, venue,
    organiser and category, the text, the conference's website. Contract: the module's (conference, base), $page (InternationalPage)
--}}
<x-theme::layout :title="$conference->title">
    @include('theme::partials.diu.hero', [
        'title' => (string) $conference->title,
        'subtitle' => null,
        'image' => $page->image('conferences') ?? theme_image('page_hero_image', 'images/page-hero.jpg'),
        'breadcrumbs' => $page->breadcrumbs($page->text('conferences_title') ?? __('international::international.conferences'), 'conferences', (string) $conference->title),
    ])

    @if ($c = $page->conference())
        <div class="bg-white">
            <article class="max-w-5xl mx-auto px-4 py-12">
                @if ($c['image'])<img src="{{ $c['image'] }}" alt="{{ $c['title'] }}" class="w-full max-h-[520px] object-cover rounded-xl mb-8">@endif
                <div class="flex flex-wrap gap-3 mb-6 text-sm">
                    @if ($c['when'])<span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-slate-700"><x-theme::icon name="calendar" class="w-4 h-4 text-primary" />{{ $c['when'] }}</span>@endif
                    @if ($c['venue'])<span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-slate-700"><x-theme::icon name="map-pin" class="w-4 h-4 text-primary" />{{ $c['venue'] }}</span>@endif
                    @if ($c['organizer'])<span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-slate-700"><x-theme::icon name="users" class="w-4 h-4 text-primary" />{{ $c['organizer'] }}</span>@endif
                    @if ($c['category'])<span class="inline-flex rounded-full bg-primary/10 px-3 py-1.5 font-medium text-primary">{{ $c['category'] }}</span>@endif
                </div>
                @if ($c['body'])<div class="prose max-w-none">{!! $c['body'] !!}</div>@endif
                @if ($c['website'])
                    <p class="mt-8"><x-theme::button :href="$c['website']" target="_blank" variant="outlineSecondary" rounded="full" padding="px-5 py-2.5" size="text-base">{{ __('international::international.page.website') }} <x-theme::icon name="arrow-up-right" class="w-4 h-4" /></x-theme::button></p>
                @endif
            </article>
        </div>
    @endif
</x-theme::layout>
