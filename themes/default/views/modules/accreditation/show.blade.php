{{--
    /accreditations/{slug}: hero with the title; the logo, the category tag, the awarding body, the dates, the text and the
    website. Contract: the module's (accreditation, base), $page (RecognitionPage)
--}}
<x-theme::layout :title="$accreditation->title">
    @include('theme::partials.diu.hero', [
        'title' => (string) $accreditation->title,
        'subtitle' => null,
        'image' => $page->image() ?? theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => $page->breadcrumbs($page->text('accreditations_title') ?? __('accreditation::accreditation.plural'), 'accreditations', (string) $accreditation->title),
    ])

    @if ($a = $page->accreditation())
        <div class="bg-white">
            <article class="max-w-5xl mx-auto px-4 py-12 grid md:grid-cols-3 gap-10">
                <aside class="space-y-4">
                    <div class="aspect-video rounded-xl border border-slate-200 bg-gradient-to-br from-slate-50 to-blue-50 flex items-center justify-center p-6">
                        @if ($a['logo'])<img loading="lazy" decoding="async" src="{{ $a['logo'] }}" alt="{{ $a['title'] }}" class="max-w-full max-h-full object-contain">@else<x-theme::icon name="graduation-cap" class="w-14 h-14 text-slate-300" />@endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($a['tag'])<span @class(['rounded-full px-3 py-1 text-xs font-semibold', 'bg-secondary text-white' => $a['accredited'], 'bg-primary/10 text-primary' => ! $a['accredited']])>{{ $a['tag'] }}</span>@endif
                        @if ($a['expired'])<span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">{{ __('accreditation::accreditation.expired') }}</span>@endif
                    </div>
                    <dl class="text-sm space-y-2">
                        @if ($a['agency'])<div><dt class="text-slate-500">{{ __('accreditation::accreditation.page.agency') }}</dt><dd class="font-medium text-slate-800">{{ $a['agency'] }}</dd></div>@endif
                        @if ($a['since'])<div><dd class="text-slate-700">{{ __('accreditation::accreditation.page.awarded', ['date' => $a['since']]) }}</dd></div>@endif
                        @if ($a['until'])<div><dd class="text-slate-700">{{ __('accreditation::accreditation.page.until', ['date' => $a['until']]) }}</dd></div>@endif
                    </dl>
                    @if ($a['website'])<x-theme::button :href="$a['website']" target="_blank" variant="outlineSecondary" rounded="full" padding="px-5 py-2.5" size="text-base">{{ __('accreditation::accreditation.page.visit') }} <x-theme::icon name="arrow-up-right" class="w-4 h-4" /></x-theme::button>@endif
                </aside>
                <div class="md:col-span-2">
                    <h1 class="text-2xl md:text-3xl font-bold text-neutral-800 mb-4">{{ $a['title'] }}</h1>
                    @if ($a['text'])<div class="prose max-w-none">{!! $a['text'] !!}</div>@endif
                </div>
            </article>
        </div>
    @endif
</x-theme::layout>
