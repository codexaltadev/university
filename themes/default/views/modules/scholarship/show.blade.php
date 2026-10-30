{{--
    /scholarships/{slug}: hero with the scholarship's name; "Browse by Section" and Quick Links on the left; its summary, last
    date, picture, details, eligibility and how to apply (DIU's article look), apply link, files, and the scholarships under it
    as folding items. Contract: the module's (scholarship, parent, parts), $page (ScholarshipPage)
--}}
<x-theme::layout :title="$scholarship->title">
    @if ($s = $page->scholarship())
        @include('theme::partials.diu.hero', [
            'title' => $s['title'],
            'subtitle' => null,
            'image' => $page->image() ?? theme_image('page_hero_image', 'images/page-hero.webp'),
            'breadcrumbs' => $page->breadcrumbs($s['title'], $s['parent']['title'] ?? null, $s['parent']['url'] ?? null),
        ])

        <div class="bg-white">
            <div class="container mx-auto px-4 sm:px-6 py-12 flex flex-col lg:flex-row gap-12" x-data="{ current: null, more: false, open: null }">
                @if (($side = $page->sideLinks()) !== [] || $page->quickLinks() !== [])
                    @include('theme::partials.diu.browse-sidebar', ['items' => $side, 'quickLinks' => $page->quickLinks(), 'width' => 'lg:w-[420px]'])
                @endif
                <article class="flex-1 min-w-0 space-y-6">
                    <h2 class="text-2xl md:text-[34px] font-semibold text-neutral-800">{{ $s['title'] }}</h2>
                    @if ($s['summary'])<p class="text-lg text-slate-600">{{ $s['summary'] }}</p>@endif
                    @if ($s['deadline'])
                        <p class="text-base text-gray-700"><span class="font-semibold">{{ __('scholarship::scholarship.page.deadline') }}:</span> {{ $s['deadline'] }}
                            <span @class(['ml-2 rounded-full px-3 py-1 text-sm font-medium', 'bg-green-100 text-green-700' => ! $s['closed'], 'bg-gray-100 text-gray-500' => $s['closed']])>{{ __($s['closed'] ? 'scholarship::scholarship.closed' : 'scholarship::scholarship.open') }}</span></p>
                    @endif
                    @if ($s['image'])<img src="{{ $s['image'] }}" alt="" class="w-full max-h-[420px] object-cover rounded-xl">@endif
                    @foreach ($s['sections'] as $section)
                        <section>
                            @if ($section['label'])<h3 class="text-xl font-bold text-blue-500 border-b border-gray-200 pb-2 mb-3">{{ $section['label'] }}</h3>@endif
                            @include('theme::partials.page.body', ['body' => $section['html']])
                        </section>
                    @endforeach
                    @if ($s['apply'] || $s['files'] !== [])
                        <div class="flex flex-wrap gap-3">
                            @if ($s['apply'])<x-theme::button :href="$s['apply']" target="_blank" variant="primary" rounded="full" padding="px-6 py-2.5" size="text-base">{{ __('scholarship::scholarship.apply') }}</x-theme::button>@endif
                            @foreach ($s['files'] as $file)
                                <x-theme::button :href="$file['url']" target="_blank" variant="outlineSecondary" rounded="full" padding="px-5 py-2.5" size="text-base"><x-theme::icon name="download" class="w-4 h-4" /> {{ $file['label'] }}</x-theme::button>
                            @endforeach
                        </div>
                    @endif
                    @if ($s['parts'] !== [])
                        <section class="space-y-3">
                            <h3 class="text-xl font-bold text-blue-500 border-b border-gray-200 pb-2">{{ __('scholarship::scholarship.opportunities') }}</h3>
                            @foreach ($s['parts'] as $item)
                                @include('theme::partials.scholarship.item', ['item' => $item])
                            @endforeach
                        </section>
                    @endif
                    <x-theme::button :href="$page->listingUrl()" variant="outlineSecondary" rounded="full" padding="px-5 py-2.5" size="text-base"><x-theme::icon name="arrow-left" class="w-4 h-4" /> {{ $page->title() }}</x-theme::button>
                </article>
            </div>
        </div>
    @endif
</x-theme::layout>
